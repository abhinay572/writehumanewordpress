import { NextResponse } from 'next/server';
import prisma from '@/lib/db';
import { verifyAdminKey } from '@/lib/auth';

/**
 * GET /api/admin/logs — every single humanization request
 *
 * Query params:
 *   ?page=1          — pagination (50 per page)
 *   ?user_id=xxx     — filter by user ID
 *   ?email=xxx       — filter by user email
 *   ?site=url        — filter by site
 *   ?mode=balanced   — filter by mode
 *   ?from=2024-01-01 — date range start
 *   ?to=2024-12-31   — date range end
 */
export async function GET(request) {
  if (!verifyAdminKey(request)) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }

  try {
    const { searchParams } = new URL(request.url);
    const page = Math.max(1, parseInt(searchParams.get('page') || '1'));
    const userId = searchParams.get('user_id') || '';
    const email = searchParams.get('email') || '';
    const site = searchParams.get('site') || '';
    const mode = searchParams.get('mode') || '';
    const from = searchParams.get('from') || '';
    const to = searchParams.get('to') || '';
    const perPage = 50;

    const where = {};

    if (userId) {
      where.userId = userId;
    }
    if (email) {
      where.user = { email: { contains: email, mode: 'insensitive' } };
    }
    if (site) {
      where.siteUrl = { contains: site, mode: 'insensitive' };
    }
    if (mode) {
      where.mode = mode;
    }
    if (from || to) {
      where.createdAt = {};
      if (from) where.createdAt.gte = new Date(from);
      if (to) where.createdAt.lte = new Date(to + 'T23:59:59Z');
    }

    const [total, logs] = await Promise.all([
      prisma.usageLog.count({ where }),
      prisma.usageLog.findMany({
        where,
        orderBy: { createdAt: 'desc' },
        skip: (page - 1) * perPage,
        take: perPage,
        include: {
          user: {
            select: {
              email: true,
              displayName: true,
              wpRole: true,
            },
          },
        },
      }),
    ]);

    return NextResponse.json({
      logs: logs.map(l => ({
        id: l.id,
        user_email: l.user.email,
        user_name: l.user.displayName,
        user_role: l.user.wpRole,
        site_url: l.siteUrl,
        input_words: l.inputWords,
        output_words: l.outputWords,
        mode: l.mode,
        tone: l.tone,
        post_id: l.postId,
        post_title: l.postTitle,
        ip_address: l.ipAddress,
        user_agent: l.userAgent,
        created_at: l.createdAt,
      })),
      total,
      page,
      per_page: perPage,
      total_pages: Math.ceil(total / perPage),
    });
  } catch (error) {
    console.error('Admin logs error:', error);
    return NextResponse.json({ error: 'Server error' }, { status: 500 });
  }
}
