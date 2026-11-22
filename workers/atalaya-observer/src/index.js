import postgres from "postgres";

const REGIONS = [
  ["frankfurt", "FRANKFURT_URL"],
  ["capetown", "CAPETOWN_URL"],
  ["singapore", "SINGAPORE_URL"],
  ["sydney", "SYDNEY_URL"],
];

export default {
  async scheduled(_controller, env, ctx) {
    ctx.waitUntil(runTick(env));
    ctx.waitUntil(runAgain(env));
  },

  async fetch(request, env, ctx) {
    const url = new URL(request.url);
    if (request.method !== "POST" || url.pathname !== "/tick") {
      return new Response("Not found", { status: 404 });
    }
    if (!bearerOk(request, env.PROBE_TOKEN)) {
      return new Response("Unauthorized", { status: 401 });
    }
    ctx.waitUntil(runTick(env));
    return Response.json({ ok: true });
  },
};

// Cloudflare cron cannot fire faster than once a minute.
// The second pass is what makes a 30 second monitor possible. A 60 second monitor stays quiet on the in between pass.
async function runAgain(env) {
  await scheduler.wait(30000);
  await runTick(env);
}

async function runTick(env) {
  if (!env.HYPERDRIVE || !env.HYPERDRIVE.connectionString) {
    console.error("Hyperdrive is not configured.");
    return;
  }

  const sql = openDb(env);

  let due = [];
  try {
    const locked = await sql`
      UPDATE observer_lock
      SET locked_until = NOW() + INTERVAL '25 seconds'
      WHERE id = 1
        AND locked_until < NOW()
      RETURNING id
    `;
    if (!locked.length) return;
    due = await sql`
    SELECT m.id, m.url, m.kind, m.phrase, m.port, u.region
    FROM monitors m
    CROSS JOIN LATERAL unnest(m.regions) AS u(region)
    WHERE m.paused = FALSE
      AND u.region IN ('frankfurt', 'capetown', 'singapore', 'sydney')
      AND NOT EXISTS (
        SELECT 1
        FROM check_results c
        WHERE c.monitor_id = m.id
          AND c.region = u.region
          AND c.checked_at > NOW() - (m.interval_ms * INTERVAL '1 millisecond')
      )
    ORDER BY (
      SELECT COALESCE(MAX(c.checked_at), TIMESTAMPTZ 'epoch')
      FROM check_results c
      WHERE c.monitor_id = m.id
        AND c.region = u.region
    ) ASC
    LIMIT 80
  `;
  } catch (error) {
    if (missingTable(error)) {
      console.error("monitors table is missing. The check schema has not been applied.");
      return;
    }
    console.error(error);
    return;
  } finally {
    await sql.end({ timeout: 5 });
  }

  const rows = await collect(env, due);
  if (!rows.length) return;

  const write = openDb(env);
  try {
    await write`
      INSERT INTO check_results ${write(rows, "monitor_id", "region", "state", "http_code", "ms", "note")}
    `;
  } catch (error) {
    console.error(error);
  } finally {
    await write.end({ timeout: 5 });
  }
}

function openDb(env) {
  return postgres(env.HYPERDRIVE.connectionString, {
    max: 1,
    fetch_types: false,
  });
}

function missingTable(error) {
  if (!error) return false;
  if (error.code === "42P01") return true;
  const message = String(error.message || "");
  return message.includes("does not exist") && (message.includes("monitors") || message.includes("check_results") || message.includes("observer_lock"));
}

async function collect(env, due) {
  const grouped = new Map();
  for (const job of due) {
    const region = String(job.region || "");
    if (!grouped.has(region)) grouped.set(region, []);
    grouped.get(region).push(job);
  }

  const batches = REGIONS.map(async ([region, key]) => {
    const jobs = grouped.get(region) || [];
    const origin = workerOrigin(env[key]);
    if (!jobs.length || !origin || !env.PROBE_TOKEN) return [];
    try {
      return await askRegion(env, origin, region, jobs);
    } catch (error) {
      console.error(region, error && error.message ? error.message : error);
      return [];
    }
  });

  return (await Promise.all(batches)).flat();
}

function workerOrigin(value) {
  if (!value || String(value).includes(".example.workers.dev")) return "";
  try {
    const url = new URL(value);
    if (url.protocol !== "https:") return "";
    return url.origin;
  } catch {
    return "";
  }
}

async function askRegion(env, origin, region, jobs) {
  const response = await fetch(origin + "/check", {
    method: "POST",
    headers: {
      Authorization: `Bearer ${env.PROBE_TOKEN}`,
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify({
      jobs: jobs.map((job) => ({
        id: job.id,
        url: job.url,
        kind: job.kind,
        phrase: job.phrase || "",
        port: Number(job.port) || 0,
      })),
    }),
    signal: AbortSignal.timeout(25000),
  });
  if (!response.ok) throw new Error("region " + response.status);
  const payload = await response.json();
  const list = payload && Array.isArray(payload.results) ? payload.results : [];
  const wanted = new Map(jobs.map((job) => [String(job.id), true]));
  const rows = [];
  for (const item of list) {
    const id = String(item && item.id || "");
    if (!wanted.has(id)) continue;
    wanted.delete(id);
    rows.push({
      monitor_id: id,
      region,
      state: cleanState(item.state),
      http_code: cleanCode(item.code),
      ms: cleanMs(item.ms),
      note: String(item.note || "").slice(0, 120),
    });
  }
  return rows;
}

function cleanState(value) {
  if (value === "up" || value === "down" || value === "answered") return value;
  return "down";
}

function cleanCode(value) {
  const code = Number(value);
  if (!Number.isInteger(code) || code < 100 || code > 599) return null;
  return code;
}

function cleanMs(value) {
  const ms = Number(value);
  if (!Number.isFinite(ms) || ms < 0) return null;
  return Math.min(120000, Math.round(ms));
}

function bearerOk(request, token) {
  const expected = `Bearer ${token || ""}`;
  const header = request.headers.get("Authorization") || "";
  if (!token || header.length !== expected.length) return false;
  let diff = 0;
  for (let i = 0; i < expected.length; i += 1) {
    diff |= expected.charCodeAt(i) ^ header.charCodeAt(i);
  }
  return diff === 0;
}
