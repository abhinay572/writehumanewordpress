import { NextResponse } from 'next/server';
import prisma from '@/lib/db';
import { verifyAdminKey } from '@/lib/auth';

/**
 * GET /api/admin/export — export all user emails + data as CSV
 *
 * Query params:
 *   ?format=csv   — CSV format (default)
 *   ?format=json  — JSON format
 *   ?site=url     — filter by site
 */
export async function GET(request) {
  if (!verifyAdminKey(request)) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }

  try {
    const { searchParams } = new URL(request.url);
    const format = searchParams.get('format') || 'csv';
    const site = searchParams.get('site') || '';

    const where = {};
    if (site) {
      where.siteUrl = { contains: site, mode: 'insensitive' };
    }

    const users = await prisma.user.findMany({
      where,
      orderBy: { lastActiveAt: 'desc' },
      select: {
        email: true,
        displayName: true,
        wpRole: true,
        siteUrl: true,
        firstSeenAt: true,
        lastActiveAt: true,
        totalWords: true,
        totalRequests: true,
        wordsThisMonth: true,
      },
    });

    if (format === 'json') {
      return NextResponse.json({ users, total: users.length });
    }

    // CSV export
    const headers = ['email', 'name', 'role', 'site', 'first_seen', 'last_active', 'total_words', 'total_requests', 'words_this_month'];
    const rows = users.map(u => [
      u.email,
      u.displayName || '',
      u.wpRole || '',
      u.siteUrl,
      u.firstSeenAt.toISOString(),
      u.lastActiveAt.toISOString(),
      u.totalWords,
      u.totalRequests,
      u.wordsThisMonth,
    ]);

    const csv = [headers.join(','), ...rows.map(r => r.map(v => `"${v}"`).join(','))].join('\n');

    return new Response(csv, {
      headers: {
        'Content-Type': 'text/csv',
        'Content-Disposition': 'attachment; filename="writehumane-users.csv"',
      },
    });
  } catch (error) {
    console.error('Admin export error:', error);
    return NextResponse.json({ error: 'Server error' }, { status: 500 });
  }
}
