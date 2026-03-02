const functions = require("firebase-functions");
const admin = require("firebase-admin");
const cors = require("cors");

admin.initializeApp();
const db = admin.firestore();

const corsHandler = cors({ origin: true });

// ============================================================
// AUTH HELPERS
// ============================================================

function verifyPluginKey(req) {
  const auth = req.headers.authorization || "";
  const key = auth.replace("Bearer ", "");
  return key === functions.config().app.plugin_key;
}

function verifyAdminKey(req) {
  const auth = req.headers.authorization || "";
  const key = auth.replace("Bearer ", "");
  return key === functions.config().app.admin_secret;
}

function getMonthStart() {
  const now = new Date();
  return new Date(now.getFullYear(), now.getMonth(), 1);
}

function getTodayStart() {
  const now = new Date();
  return new Date(now.getFullYear(), now.getMonth(), now.getDate());
}

// ============================================================
// POST /track — WordPress plugin calls this after every humanization
// ============================================================

exports.track = functions.https.onRequest((req, res) => {
  corsHandler(req, res, async () => {
    if (req.method === "OPTIONS") return res.status(204).send("");
    if (req.method !== "POST") return res.status(405).json({ error: "Method not allowed" });

    if (!verifyPluginKey(req)) {
      return res.status(401).json({ error: "Unauthorized" });
    }

    try {
      const {
        user_email, user_name, user_role, wp_user_id,
        site_url, site_name, wp_version, plugin_version, php_version,
        input_words, output_words, mode, tone,
        post_id, post_title, ip_address, user_agent,
      } = req.body;

      if (!user_email || !site_url || !input_words) {
        return res.status(400).json({ error: "Missing required fields" });
      }

      const now = admin.firestore.FieldValue.serverTimestamp();
      const inputWordsNum = parseInt(input_words) || 0;
      const outputWordsNum = parseInt(output_words) || 0;

      // User doc ID = email + site combo (safe for Firestore)
      const userId = Buffer.from(`${user_email}||${site_url}`).toString("base64url");

      const userRef = db.collection("users").doc(userId);
      const userDoc = await userRef.get();

      const monthStart = getMonthStart();

      if (userDoc.exists) {
        const data = userDoc.data();
        const resetAt = data.monthResetAt ? data.monthResetAt.toDate() : new Date(0);

        // Check if we need to reset monthly counter
        if (resetAt < monthStart) {
          await userRef.update({
            displayName: user_name || data.displayName,
            wpRole: user_role || data.wpRole,
            wpUserId: wp_user_id ? parseInt(wp_user_id) : data.wpUserId,
            lastActiveAt: now,
            totalWords: admin.firestore.FieldValue.increment(inputWordsNum),
            totalRequests: admin.firestore.FieldValue.increment(1),
            wordsThisMonth: inputWordsNum, // Reset + new
            monthResetAt: monthStart,
          });
        } else {
          await userRef.update({
            displayName: user_name || data.displayName,
            wpRole: user_role || data.wpRole,
            wpUserId: wp_user_id ? parseInt(wp_user_id) : data.wpUserId,
            lastActiveAt: now,
            totalWords: admin.firestore.FieldValue.increment(inputWordsNum),
            totalRequests: admin.firestore.FieldValue.increment(1),
            wordsThisMonth: admin.firestore.FieldValue.increment(inputWordsNum),
          });
        }
      } else {
        await userRef.set({
          email: user_email,
          displayName: user_name || null,
          wpUserId: wp_user_id ? parseInt(wp_user_id) : null,
          wpRole: user_role || null,
          siteUrl: site_url,
          firstSeenAt: now,
          lastActiveAt: now,
          totalWords: inputWordsNum,
          totalRequests: 1,
          wordsThisMonth: inputWordsNum,
          monthResetAt: monthStart,
        });
      }

      // Log the request
      await db.collection("usage_logs").add({
        userId,
        userEmail: user_email,
        userName: user_name || null,
        siteUrl: site_url,
        inputWords: inputWordsNum,
        outputWords: outputWordsNum,
        mode: mode || "balanced",
        tone: tone || null,
        postId: post_id ? parseInt(post_id) : null,
        postTitle: post_title || null,
        ipAddress: ip_address || null,
        userAgent: user_agent || null,
        createdAt: now,
      });

      // Upsert site
      const siteId = Buffer.from(site_url).toString("base64url");
      const siteRef = db.collection("sites").doc(siteId);
      const siteDoc = await siteRef.get();

      if (siteDoc.exists) {
        await siteRef.update({
          siteName: site_name || siteDoc.data().siteName,
          wpVersion: wp_version || siteDoc.data().wpVersion,
          pluginVersion: plugin_version || siteDoc.data().pluginVersion,
          phpVersion: php_version || siteDoc.data().phpVersion,
          lastActiveAt: now,
          totalWords: admin.firestore.FieldValue.increment(inputWordsNum),
          totalRequests: admin.firestore.FieldValue.increment(1),
        });
      } else {
        await siteRef.set({
          siteUrl: site_url,
          siteName: site_name || null,
          wpVersion: wp_version || null,
          pluginVersion: plugin_version || null,
          phpVersion: php_version || null,
          firstSeenAt: now,
          lastActiveAt: now,
          totalWords: inputWordsNum,
          totalRequests: 1,
          totalUsers: 1,
        });
      }

      return res.status(200).json({ success: true });
    } catch (error) {
      console.error("Track error:", error);
      return res.status(500).json({ error: "Server error" });
    }
  });
});

// ============================================================
// GET /admin-stats — full dashboard overview for you
// ============================================================

exports.adminStats = functions.https.onRequest((req, res) => {
  corsHandler(req, res, async () => {
    if (req.method === "OPTIONS") return res.status(204).send("");
    if (!verifyAdminKey(req)) return res.status(401).json({ error: "Unauthorized" });

    try {
      const monthStart = getMonthStart();
      const todayStart = getTodayStart();
      const thirtyDaysAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000);

      // All users
      const usersSnap = await db.collection("users").get();
      const users = [];
      let totalWords = 0;
      let totalRequests = 0;
      let monthlyWords = 0;
      let monthlyRequests = 0;
      let monthlyActiveUsers = 0;
      let newUsersThisMonth = 0;

      usersSnap.forEach((doc) => {
        const d = doc.data();
        users.push({ id: doc.id, ...d });
        totalWords += d.totalWords || 0;
        totalRequests += d.totalRequests || 0;
        monthlyWords += d.wordsThisMonth || 0;

        const lastActive = d.lastActiveAt ? d.lastActiveAt.toDate() : new Date(0);
        if (lastActive >= monthStart) {
          monthlyActiveUsers++;
          monthlyRequests += d.wordsThisMonth > 0 ? 1 : 0; // approx
        }

        const firstSeen = d.firstSeenAt ? d.firstSeenAt.toDate() : new Date(0);
        if (firstSeen >= monthStart) {
          newUsersThisMonth++;
        }
      });

      // All sites
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

      // Today's logs
      const todaySnap = await db.collection("usage_logs")
        .where("createdAt", ">=", todayStart)
        .get();
      let todayWords = 0;
      let todayRequests = 0;
      todaySnap.forEach((doc) => {
        const d = doc.data();
        todayWords += d.inputWords || 0;
        todayRequests++;
      });

      // Last 30 days logs for chart + mode breakdown
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

      // Top 10 users by words this month
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
        today: {
          words: todayWords,
          requests: todayRequests,
        },
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
  });
});

// ============================================================
// GET /admin-users — all users with search/filter/sort
// ============================================================

exports.adminUsers = functions.https.onRequest((req, res) => {
  corsHandler(req, res, async () => {
    if (req.method === "OPTIONS") return res.status(204).send("");
    if (!verifyAdminKey(req)) return res.status(401).json({ error: "Unauthorized" });

    try {
      const search = (req.query.search || "").toLowerCase();
      const site = (req.query.site || "").toLowerCase();
      const sort = req.query.sort || "recent";

      const usersSnap = await db.collection("users").get();
      let users = [];

      usersSnap.forEach((doc) => {
        const d = doc.data();
        users.push({ id: doc.id, ...d });
      });

      // Filter
      if (search) {
        users = users.filter((u) =>
          (u.email || "").toLowerCase().includes(search) ||
          (u.displayName || "").toLowerCase().includes(search)
        );
      }
      if (site) {
        users = users.filter((u) => (u.siteUrl || "").toLowerCase().includes(site));
      }

      // Sort
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
  });
});

// ============================================================
// GET /admin-logs — every humanization request
// ============================================================

exports.adminLogs = functions.https.onRequest((req, res) => {
  corsHandler(req, res, async () => {
    if (req.method === "OPTIONS") return res.status(204).send("");
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
  });
});

// ============================================================
// GET /admin-export — export all user emails + data as CSV or JSON
// ============================================================

exports.adminExport = functions.https.onRequest((req, res) => {
  corsHandler(req, res, async () => {
    if (req.method === "OPTIONS") return res.status(204).send("");
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

      // CSV
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
  });
});
