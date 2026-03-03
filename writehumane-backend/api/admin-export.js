const { db } = require("../lib/firebase");
const { verifyAdminKey, setCors } = require("../lib/auth");

module.exports = async function handler(req, res) {
  setCors(res);
  if (req.method === "OPTIONS") return res.status(204).end();
  if (!verifyAdminKey(req)) return res.status(401).json({ error: "Unauthorized" });

  try {
    const format = req.query.format || "csv";

    const usersSnap = await db.collection("users").get();
    const users = [];

    usersSnap.forEach((doc) => {
      const d = doc.data();
      users.push({
        email: d.email,
        name: d.displayName || "",
        role: d.wpRole || "",
        site: d.siteUrl || "",
        first_seen: d.firstSeenAt ? d.firstSeenAt.toDate().toISOString() : "",
        last_active: d.lastActiveAt ? d.lastActiveAt.toDate().toISOString() : "",
        total_words: d.totalWords || 0,
        total_requests: d.totalRequests || 0,
        words_this_month: d.wordsThisMonth || 0,
      });
    });

    if (format === "json") {
      return res.status(200).json({ users, total: users.length });
    }

    const headers = "email,name,role,site,first_seen,last_active,total_words,total_requests,words_this_month";
    const rows = users.map((u) =>
      `"${u.email}","${u.name}","${u.role}","${u.site}","${u.first_seen}","${u.last_active}",${u.total_words},${u.total_requests},${u.words_this_month}`
    );
    const csv = [headers, ...rows].join("\n");

    res.setHeader("Content-Type", "text/csv");
    res.setHeader("Content-Disposition", "attachment; filename=writehumane-users.csv");
    return res.status(200).send(csv);
  } catch (error) {
    console.error("Admin export error:", error);
    return res.status(500).json({ error: "Server error" });
  }
};
