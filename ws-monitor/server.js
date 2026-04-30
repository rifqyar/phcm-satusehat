const { WebSocketServer } = require("ws");
const Redis = require("ioredis");

const redis = new Redis({ host: "127.0.0.1", port: 6379 });
const wss = new WebSocketServer({ port: 6001 });

const QUEUES = [
    "encounter",
    "Condition",
    "observasi",
    "procedure",
    "Composition",
    "Immunization",
    "MedicationRequest",
    "MedicationDispense",
    "AllergyIntolerance",
    "ServiceRequest",
    "ClinicalImpression",
    "specimen",
    "DiagnosticReport",
    "CarePlan",
    "EpisodeOfCare",
    "QuestionnaireResponse",
];

function decodeJobParams(data) {
    if (!data || !data.command) return {};
    try {
        // Laravel serializes the job command — extract readable fields
        const raw = data.command;
        const result = {};

        // extract simple string/number properties from serialized string
        const matches = raw.matchAll(
            /s:\d+:"([^"]+)";(?:s:\d+:"([^"]+)"|i:(\d+)|d:([0-9.]+))/g,
        );
        for (const m of matches) {
            if (m[1] && (m[2] || m[3] || m[4])) {
                result[m[1]] = m[2] || m[3] || m[4];
            }
        }
        return result;
    } catch {
        return {};
    }
}

async function getQueueData() {
    const data = [];

    for (const q of QUEUES) {
        const rawJobs = [
            ...(await redis.lrange(`queues:${q}`, 0, -1)),
            ...(await redis.zrange(`queues:${q}:reserved`, 0, -1)),
            ...(await redis.zrange(`queues:${q}:delayed`, 0, -1)),
        ];

        const jobs = rawJobs
            .map((raw) => {
                try {
                    const payload = JSON.parse(raw);
                    return {
                        id: payload.uuid || payload.id || null,
                        job_class: payload.displayName || payload.job || null,
                        attempts: payload.attempts || 0,
                        pushed_at: payload.pushedAt
                            ? new Date(payload.pushedAt * 1000).toISOString()
                            : null,
                        params: decodeJobParams(payload.data || {}),
                    };
                } catch {
                    return null;
                }
            })
            .filter(Boolean);

        data.push({
            queue: q,
            pending: jobs, // full job array, not just count
            count: jobs.length,
        });
    }

    return data;
}

async function broadcast() {
    if (wss.clients.size === 0) return;
    const data = await getQueueData();
    const payload = JSON.stringify({ type: "queue_update", data });
    wss.clients.forEach((client) => {
        if (client.readyState === 1) client.send(payload);
    });
}

setInterval(broadcast, 3000);

wss.on("connection", async (ws) => {
    console.log("Client connected");
    const data = await getQueueData();
    ws.send(JSON.stringify({ type: "queue_update", data }));
});

console.log("WS running on ws://localhost:6001");
