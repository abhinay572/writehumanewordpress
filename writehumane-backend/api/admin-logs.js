const { db } = require("../lib/firebase");
const { verifyAdminKey, setCors } = require("../lib/auth");

module.exports = async function handler(req, res) {
  setCors(res);
  if (req.method === "OPTIONS") return res.status(204).end();
  if (!verifyAdminKey(req)) return res.status(401).json({ error: "Unauthorized" });

  try {
    const email = req.query.email || "";
    const site = req.query.site || "";
    const mode = req.query.mode || "";
    const limit = Math.min(parseInt(req.query.limit) || 50, 200);

    let query = db.collection("usage_logs").orderBy("createdAt", "desc");

    if (email) {
      query = db.collection("usage_logs")
        .where("userEmail", "==", email)
        .orderBy("createdAt", "desc");
    } else if (site) {
      query = db.collection("usage_logs")
        .where("siteUrl", "==", site)
        .orderBy("createdAt", "desc");
    } else if (mode) {
      query = db.collection("usage_logs")
        .where("mode", "==", mode)
        .orderBy("createdAt", "desc");
    }

    const snap = await query.limit(limit).get();
    const logs = [];

    snap.forEach((doc) => {
      const d = doc.data();
      logs.push({
        id: doc.id,
        user_email: d.userEmail,
        user_name: d.userName,
        site_url: d.siteUrl,
        input_words: d.inputWords,
        output_words: d.outputWords,
        mode: d.mode,
        tone: d.tone,
        post_id: d.postId,
        post_title: d.postTitle,
        ip_address: d.ipAddress,
        user_agent: d.userAgent,
        created_at: d.createdAt,
      });
    });

    return res.status(200).json({ logs, total: logs.length });
  } catch (error) {
    console.error("Admin logs error:", error);
    return res.status(500).json({ error: "Server error" });
  }
};
