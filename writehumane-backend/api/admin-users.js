const { db } = require("../lib/firebase");
const { verifyAdminKey, setCors } = require("../lib/auth");

module.exports = async function handler(req, res) {
  setCors(res);
  if (req.method === "OPTIONS") return res.status(204).end();
  if (!verifyAdminKey(req)) return res.status(401).json({ error: "Unauthorized" });

  try {
    const search = (req.query.search || "").toLowerCase();
    const site = (req.query.site || "").toLowerCase();
    const sort = req.query.sort || "recent";

    const usersSnap = await db.collection("users").get();
    let users = [];

    usersSnap.forEach((doc) => {
      users.push({ id: doc.id, ...doc.data() });
    });

    if (search) {
      users = users.filter((u) =>
        (u.email || "").toLowerCase().includes(search) ||
        (u.displayName || "").toLowerCase().includes(search)
      );
    }
    if (site) {
      users = users.filter((u) => (u.siteUrl || "").toLowerCase().includes(site));
    }

    const sortFns = {
      words: (a, b) => (b.totalWords || 0) - (a.totalWords || 0),
      requests: (a, b) => (b.totalRequests || 0) - (a.totalRequests || 0),
      recent: (a, b) => {
        const aTime = a.lastActiveAt ? a.lastActiveAt.toDate().getTime() : 0;
        const bTime = b.lastActiveAt ? b.lastActiveAt.toDate().getTime() : 0;
        return bTime - aTime;
      },
      oldest: (a, b) => {
        const aTime = a.firstSeenAt ? a.firstSeenAt.toDate().getTime() : 0;
        const bTime = b.firstSeenAt ? b.firstSeenAt.toDate().getTime() : 0;
        return aTime - bTime;
      },
    };
    users.sort(sortFns[sort] || sortFns.recent);

    return res.status(200).json({
      users: users.map((u) => ({
        id: u.id,
        email: u.email,
        display_name: u.displayName,
        wp_user_id: u.wpUserId,
        wp_role: u.wpRole,
        site_url: u.siteUrl,
        first_seen: u.firstSeenAt,
        last_active: u.lastActiveAt,
        total_words: u.totalWords || 0,
        total_requests: u.totalRequests || 0,
        words_this_month: u.wordsThisMonth || 0,
      })),
      total: users.length,
    });
  } catch (error) {
    console.error("Admin users error:", error);
    return res.status(500).json({ error: "Server error" });
  }
};
