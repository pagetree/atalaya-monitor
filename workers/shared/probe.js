import { connect } from "cloudflare:sockets";

const TIMEOUT_MS = 12000;
const PORT_TIMEOUT_MS = 5000;
const BODY_LIMIT = 500000;

export async function probe(job) {
  try {
    if (cleanKind(job.kind) === "port") return await probePort(job);
    return await probeHttp(job);
  } catch {
    return row(job, "down", null, null, "no response");
  }
}

function cleanKind(value) {
  if (value === "api" || value === "keyword" || value === "website" || value === "port") return value;
  return "website";
}

function row(job, state, code, ms, note) {
  return {
    id: String(job.id || ""),
    state,
    code,
    ms,
    note: note || "",
  };
}

function blockedHost(hostname) {
  const host = String(hostname || "").toLowerCase().replace(/^\[|\]$/g, "");
  if (!host || host === "localhost" || host.endsWith(".localhost") || host.endsWith(".local")) return true;
  if (host === "0.0.0.0" || host === "::" || host === "::1") return true;
  if (/^127\./.test(host) || /^10\./.test(host) || /^192\.168\./.test(host) || /^169\.254\./.test(host)) return true;
  if (/^172\.(1[6-9]|2\d|3[0-1])\./.test(host)) return true;
  if (host.startsWith("fc") || host.startsWith("fd") || host.startsWith("fe80:")) return true;
  return false;
}

function allowedUrl(value) {
  if (!value || (value.protocol !== "http:" && value.protocol !== "https:")) return false;
  if (!value.hostname || blockedHost(value.hostname)) return false;
  return true;
}

async function probePort(job) {
  let parsed;
  try {
    parsed = new URL(job.url);
  } catch {
    return row(job, "down", null, null, "closed");
  }
  const host = parsed.hostname.replace(/^\[|\]$/g, "");
  const port = Math.round(Number(job.port));
  if (!host || blockedHost(host) || port < 1 || port > 65535) {
    return row(job, "down", null, null, "closed");
  }

  const started = Date.now();
  let socket;
  try {
    socket = connect({ hostname: host, port }, { secureTransport: "off", allowHalfOpen: false });
    const opened = socket.opened.then(() => "open");
    const timeout = scheduler.wait(PORT_TIMEOUT_MS).then(() => "timeout");
    const winner = await Promise.race([opened, timeout]);
    if (winner !== "open") return row(job, "down", null, Date.now() - started, "closed");
    return row(job, "up", null, Date.now() - started, "");
  } catch {
    return row(job, "down", null, Date.now() - started, "closed");
  } finally {
    if (socket) {
      try { await socket.close(); } catch { /* already closed */ }
    }
  }
}

async function probeHttp(job) {
  const kind = cleanKind(job.kind);
  let current;
  try {
    current = new URL(job.url);
  } catch {
    return row(job, "down", null, null, "no response");
  }
  if (!allowedUrl(current)) return row(job, "down", null, null, "no response");

  const started = Date.now();
  let response;
  try {
    for (let hop = 0; hop < 5; hop += 1) {
      response = await fetch(current.href, {
        method: "GET",
        redirect: "manual",
        cache: "no-store",
        headers: { "User-Agent": "Atalaya" },
        signal: AbortSignal.timeout(TIMEOUT_MS),
      });
      if (response.status < 300 || response.status >= 400) break;
      const location = response.headers.get("Location");
      if (response.body) await response.body.cancel();
      if (!location) return row(job, "down", response.status, Date.now() - started, "no response");
      const next = new URL(location, current);
      if (!allowedUrl(next)) return row(job, "down", null, Date.now() - started, "no response");
      current = next;
      response = null;
    }
  } catch (error) {
    const timedOut = error && (error.name === "TimeoutError" || error.name === "AbortError");
    return row(job, "down", null, null, timedOut ? "timed out" : "no response");
  }

  if (!response) return row(job, "down", null, Date.now() - started, "no response");
  const ms = Date.now() - started;
  if (!response.ok) {
    await dropBody(response);
    return row(job, "down", response.status, ms, "");
  }

  if (kind === "api") {
    const answered = !looksLikeApi(response.headers.get("content-type"));
    await dropBody(response);
    if (answered) return row(job, "answered", response.status, ms, "html page");
    return row(job, "up", response.status, ms, "");
  }

  if (kind === "keyword") {
    const phrase = String(job.phrase || "").trim().toLowerCase();
    if (!phrase) {
      await dropBody(response);
      return row(job, "down", response.status, ms, "phrase missing");
    }
    const body = (await readLimited(response)).toLowerCase();
    if (!body.includes(phrase)) return row(job, "down", response.status, ms, "phrase missing");
    return row(job, "up", response.status, ms, "");
  }

  await dropBody(response);
  return row(job, "up", response.status, ms, "");
}

async function dropBody(response) {
  if (response.body) {
    try { await response.body.cancel(); } catch { /* already consumed */ }
  }
}

function looksLikeApi(type) {
  type = String(type || "").toLowerCase();
  if (!type) return true;
  if (type.includes("json") || type.includes("xml") || type.includes("text/plain")) return true;
  if (type.includes("text/csv") || type.includes("grpc") || type.includes("octet-stream")) return true;
  return false;
}

async function readLimited(response) {
  if (!response.body) return "";
  const reader = response.body.getReader();
  const chunks = [];
  let size = 0;
  try {
    while (size < BODY_LIMIT) {
      const step = await reader.read();
      if (step.done) break;
      size += step.value.byteLength;
      chunks.push(step.value);
    }
  } finally {
    try { await reader.cancel(); } catch { /* already done */ }
  }
  const bytes = new Uint8Array(Math.min(size, BODY_LIMIT));
  let offset = 0;
  for (const chunk of chunks) {
    const take = Math.min(chunk.byteLength, bytes.length - offset);
    if (take <= 0) break;
    bytes.set(chunk.subarray(0, take), offset);
    offset += take;
  }
  return new TextDecoder().decode(bytes);
}
