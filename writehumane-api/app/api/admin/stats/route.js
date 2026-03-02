import { NextResponse } from 'next/server';
import prisma from '@/lib/db';
import { verifyAdminKey } from '@/lib/auth';

/**
 * GET /api/admin/stats — overview dashboard for you
 *
 * Returns: total users, total words, total requests, active sites,
 *          today's stats, this month's stats, daily chart (30 days),
 *          mode breakdown, top users, top sites
 */
export async function GET(request) {
  if (!verifyAdminKey(request)) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }

  try {
    const now = new Date();
    const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);
    const thirtyDaysAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);

    // All-time totals
    const [totalUsers, totalSites, allTimeLogs] = await Promise.all([
      prisma.user.count(),
      prisma.site.count(),
      prisma.usageLog.aggregate({
        _sum: { inputWords: true, outputWords: true },
        _count: true,
      }),
    ]);

    // Today
    const todayLogs = await prisma.usageLog.aggregate({
      where: { createdAt: { gte: todayStart } },
      _sum: { inputWords: true },
      _count: true,
    });

    const todayActiveUsers = await prisma.usageLog.groupBy({
      by: ['userId'],
      where: { createdAt: { gte: todayStart } },
    });

    // This month
    const monthLogs = await prisma.usageLog.aggregate({
      where: { createdAt: { gte: monthStart } },
      _sum: { inputWords: true, outputWords: true },
      _count: true,
    });

    const monthActiveUsers = await prisma.usageLog.groupBy({
      by: ['userId'],
      where: { createdAt: { gte: monthStart } },
    });

    // New users this month
    const newUsersThisMonth = await prisma.user.count({
      where: { firstSeenAt: { gte: monthStart } },
    });

    // Daily breakdown (last 30 days)
    const dailyRaw = await prisma.$queryRaw`
      SELECT DATE("createdAt") as day,
             SUM("inputWords") as words,
             COUNT(*)::int as requests,
             COUNT(DISTINCT "userId")::int as users
      FROM "UsageLog"
      WHERE "createdAt" >= ${thirtyDaysAgo}
      GROUP BY DATE("createdAt")
      ORDER BY day ASC
    `;

    // Mode breakdown this month
    const modeBreakdown = await prisma.usageLog.groupBy({
      by: ['mode'],
      where: { createdAt: { gte: monthStart } },
      _sum: { inputWords: true },
      _count: true,
    });

    // Top 10 users by words this month
    const topUsers = await prisma.user.findMany({
      where: { lastActiveAt: { gte: monthStart } },
      orderBy: { wordsThisMonth: 'desc' },
      take: 10,
      select: {
        id: true,
        email: true,
        displayName: true,
        wpRole: true,
        siteUrl: true,
        wordsThisMonth: true,
        totalWords: true,
        totalRequests: true,
        lastActiveAt: true,
        firstSeenAt: true,
      },
    });

    // All sites
    const sites = await prisma.site.findMany({
      orderBy: { lastActiveAt: 'desc' },
      take: 20,
    });

    return NextResponse.json({
      all_time: {
        total_users: totalUsers,
        total_sites: totalSites,
        total_words: allTimeLogs._sum.inputWords || 0,
        total_output_words: allTimeLogs._sum.outputWords || 0,
        total_requests: allTimeLogs._count || 0,
      },
      today: {
        words: todayLogs._sum.inputWords || 0,
        requests: todayLogs._count || 0,
        active_users: todayActiveUsers.length,
      },
      this_month: {
        words: monthLogs._sum.inputWords || 0,
        output_words: monthLogs._sum.outputWords || 0,
        requests: monthLogs._count || 0,
        active_users: monthActiveUsers.length,
        new_users: newUsersThisMonth,
      },
      daily_chart: dailyRaw,
      mode_breakdown: modeBreakdown.map(m => ({
        mode: m.mode,
        requests: m._count,
        words: m._sum.inputWords || 0,
      })),
      top_users: topUsers,
      sites: sites,
    });
  } catch (error) {
    console.error('Admin stats error:', error);
    return NextResponse.json({ error: 'Server error' }, { status: 500 });
  }
}
