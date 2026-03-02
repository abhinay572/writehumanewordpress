import { NextResponse } from 'next/server';
import prisma from '@/lib/db';
import { verifyAdminKey } from '@/lib/auth';

/**
 * GET /api/admin/users — all users with full details
 *
 * Query params:
 *   ?page=1        — pagination (25 per page)
 *   ?search=email  — search by email or name
 *   ?site=url      — filter by site
 *   ?sort=words    — sort by: words, requests, recent, oldest
 */
export async function GET(request) {
  if (!verifyAdminKey(request)) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }

  try {
    const { searchParams } = new URL(request.url);
    const page = Math.max(1, parseInt(searchParams.get('page') || '1'));
    const search = searchParams.get('search') || '';
    const site = searchParams.get('site') || '';
    const sort = searchParams.get('sort') || 'recent';
    const perPage = 25;

    // Build where clause
    const where = {};
    if (search) {
      where.OR = [
        { email: { contains: search, mode: 'insensitive' } },
        { displayName: { contains: search, mode: 'insensitive' } },
      ];
    }
    if (site) {
      where.siteUrl = { contains: site, mode: 'insensitive' };
    }

    // Sort
    const orderBy = {
      words: { totalWords: 'desc' },
      requests: { totalRequests: 'desc' },
      recent: { lastActiveAt: 'desc' },
      oldest: { firstSeenAt: 'asc' },
    }[sort] || { lastActiveAt: 'desc' };

    const [total, users] = await Promise.all([
      prisma.user.count({ where }),
      prisma.user.findMany({
        where,
        orderBy,
        skip: (page - 1) * perPage,
        take: perPage,
        include: {
          _count: { select: { logs: true } },
          logs: {
            orderBy: { createdAt: 'desc' },
            take: 5,
            select: {
              id: true,
              inputWords: true,
              outputWords: true,
              mode: true,
              tone: true,
              postTitle: true,
              createdAt: true,
            },
          },
        },
      }),
    ]);

    return NextResponse.json({
      users: users.map(u => ({
        id: u.id,
        email: u.email,
        display_name: u.displayName,
        wp_user_id: u.wpUserId,
        wp_role: u.wpRole,
        site_url: u.siteUrl,
        first_seen: u.firstSeenAt,
        last_active: u.lastActiveAt,
        total_words: u.totalWords,
        total_requests: u.totalRequests,
        words_this_month: u.wordsThisMonth,
        log_count: u._count.logs,
        recent_activity: u.logs,
      })),
      total,
      page,
      per_page: perPage,
      total_pages: Math.ceil(total / perPage),
    });
  } catch (error) {
    console.error('Admin users error:', error);
    return NextResponse.json({ error: 'Server error' }, { status: 500 });
  }
}
