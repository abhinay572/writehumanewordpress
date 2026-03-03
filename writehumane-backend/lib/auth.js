function verifyPluginKey(req) {
  const auth = req.headers.authorization || "";
  const key = auth.replace("Bearer ", "");
  return key === process.env.PLUGIN_KEY;
}

function verifyAdminKey(req) {
  const auth = req.headers.authorization || "";
  const key = auth.replace("Bearer ", "");
  return key === process.env.ADMIN_SECRET;
}

function setCors(res) {
  res.setHeader("Access-Control-Allow-Origin", "*");
  res.setHeader("Access-Control-Allow-Methods", "GET,POST,OPTIONS");
  res.setHeader("Access-Control-Allow-Headers", "Content-Type,Authorization");
}

module.exports = { verifyPluginKey, verifyAdminKey, setCors };
