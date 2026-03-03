module.exports = function handler(req, res) {
  res.status(200).json({
    service: "WriteHumane API",
    status: "ok",
    endpoints: ["/track", "/admin-stats", "/admin-users", "/admin-logs", "/admin-export"],
  });
};
