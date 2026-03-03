const { admin, db } = require("../lib/firebase");
const { verifyPluginKey, setCors } = require("../lib/auth");

module.exports = async function handler(req, res) {
  setCors(res);
  if (req.method === "OPTIONS") return res.status(204).end();
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

    const userId = Buffer.from(`${user_email}||${site_url}`).toString("base64url");
    const userRef = db.collection("users").doc(userId);
    const userDoc = await userRef.get();

    const monthStart = new Date(new Date().getFullYear(), new Date().getMonth(), 1);

    if (userDoc.exists) {
      const data = userDoc.data();
      const resetAt = data.monthResetAt ? data.monthResetAt.toDate() : new Date(0);

      if (resetAt < monthStart) {
        await userRef.update({
          displayName: user_name || data.displayName,
          wpRole: user_role || data.wpRole,
          wpUserId: wp_user_id ? parseInt(wp_user_id) : data.wpUserId,
          lastActiveAt: now,
          totalWords: admin.firestore.FieldValue.increment(inputWordsNum),
          totalRequests: admin.firestore.FieldValue.increment(1),
          wordsThisMonth: inputWordsNum,
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
};
