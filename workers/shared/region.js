import { probe } from "./probe.js";

const MAX_JOBS = 40;
const CONCURRENCY = 6;

export async function handle(request, env) {
  const url = new URL(request.url);
  if (request.method !== "POST" || url.pathname !== "/check") {
    return new Response("Not found", { status: 404 });
  }
  if (!authorized(request, env.PROBE_TOKEN)) {
    return new Response("Unauthorized", { status: 401 });
  }

  const length = Number(request.headers.get("Content-Length") || 0);
  if (length > 100000) return Response.json({ results: [] }, { status: 413 });

  let body;
  try {
    body = await request.json();
  } catch {
    return Response.json({ results: [] }, { status: 400 });
  }

  const jobs = Array.isArray(body.jobs) ? body.jobs.slice(0, MAX_JOBS) : [];
  const results = await mapPool(jobs, CONCURRENCY, probe);
  return Response.json({ region: env.REGION || "", results });
}

function authorized(request, token) {
  const expected = `Bearer ${token || ""}`;
  const header = request.headers.get("Authorization") || "";
  if (!token || header.length !== expected.length) return false;
  let diff = 0;
  for (let i = 0; i < expected.length; i += 1) {
    diff |= expected.charCodeAt(i) ^ header.charCodeAt(i);
  }
  return diff === 0;
}

async function mapPool(items, limit, fn) {
  const results = new Array(items.length);
  let next = 0;
  async function worker() {
    while (next < items.length) {
      const index = next;
      next += 1;
      results[index] = await fn(items[index]);
    }
  }
  const runners = [];
  const count = Math.min(limit, items.length);
  for (let i = 0; i < count; i += 1) runners.push(worker());
  await Promise.all(runners);
  return results;
}
