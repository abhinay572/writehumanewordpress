const { admin, db } = require("../lib/firebase");
const { verifyAdminKey, setCors } = require("../lib/auth");

module.exports = async function handler(req, res) {
  setCors(res);
  if (req.method === "OPTIONS") return res.status(204).end();
  if (!verifyAdminKey(req)) return res.status(401).json({ error: "Unauthorized" });

  try {
    const now = new Date();
    const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);
    const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const thirtyDaysAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000);

    const usersSnap = await db.collection("users").get();
    const users = [];
    let totalWords = 0;
    let totalRequests = 0;
    let monthlyWords = 0;
    let monthlyActiveUsers = 0;
    let newUsersThisMonth = 0;

    usersSnap.forEach((doc) => {
      const d = doc.data();
      users.push({ id: doc.id, ...d });
      totalWords += d.totalWords || 0;
      totalRequests += d.totalRequests || 0;
      monthlyWords += d.wordsThisMonth || 0;

      const lastActive = d.lastActiveAt ? d.lastActiveAt.toDate() : new Date(0);
      if (lastActive >= monthStart) monthlyActiveUsers++;

      const firstSeen = d.firstSeenAt ? d.firstSeenAt.toDate() : new Date(0);
      if (firstSeen >= monthStart) newUsersThisMonth++;
    });

    const sitesSnap = await db.collection("sites").get();
    const sites = [];
    sitesSnap.forEach((doc) => {
      const d = doc.data();
      sites.push({
        id: doc.id,
        site_url: d.siteUrl,
        site_name: d.siteName,
        wp_version: d.wpVersion,
        plugin_version: d.pluginVersion,
        total_words: d.totalWords || 0,
        total_requests: d.totalRequests || 0,
        last_active: d.lastActiveAt,
      });
    });

    const todaySnap = await db.collection("usage_logs")
      .where("createdAt", ">=", todayStart)
      .get();
    let todayWords = 0;
    let todayRequests = 0;
    todaySnap.forEach((doc) => {
      todayWords += doc.data().inputWords || 0;
      todayRequests++;
    });

    const recentSnap = await db.collection("usage_logs")
      .where("createdAt", ">=", thirtyDaysAgo)
      .orderBy("createdAt", "asc")
      .get();

    const dailyMap = {};
    const modeMap = {};
    let monthReqCount = 0;

    recentSnap.forEach((doc) => {
      const d = doc.data();
      const date = d.createdAt ? d.createdAt.toDate() : new Date();
      const dayKey = date.toISOString().split("T")[0];

      if (!dailyMap[dayKey]) dailyMap[dayKey] = { day: dayKey, words: 0, requests: 0 };
      dailyMap[dayKey].words += d.inputWords || 0;
      dailyMap[dayKey].requests++;

      if (date >= monthStart) {
        monthReqCount++;
        const m = d.mode || "balanced";
        if (!modeMap[m]) modeMap[m] = { mode: m, requests: 0, words: 0 };
        modeMap[m].requests++;
        modeMap[m].words += d.inputWords || 0;
      }
    });

    const topUsers = users
      .filter((u) => u.wordsThisMonth > 0)
      .sort((a, b) => (b.wordsThisMonth || 0) - (a.wordsThisMonth || 0))
      .slice(0, 10)
      .map((u) => ({
        email: u.email,
        display_name: u.displayName,
        wp_role: u.wpRole,
        site_url: u.siteUrl,
        words_this_month: u.wordsThisMonth,
        total_words: u.totalWords,
        total_requests: u.totalRequests,
        last_active: u.lastActiveAt,
        first_seen: u.firstSeenAt,
      }));

    return res.status(200).json({
      all_time: {
        total_users: users.length,
        total_sites: sites.length,
        total_words: totalWords,
        total_requests: totalRequests,
      },
      today: { words: todayWords, requests: todayRequests },
      this_month: {
        words: monthlyWords,
        requests: monthReqCount,
        active_users: monthlyActiveUsers,
        new_users: newUsersThisMonth,
      },
      daily_chart: Object.values(dailyMap),
      mode_breakdown: Object.values(modeMap),
      top_users: topUsers,
      sites,
    });
  } catch (error) {
    console.error("Admin stats error:", error);
    return res.status(500).json({ error: "Server error" });
  }
};
