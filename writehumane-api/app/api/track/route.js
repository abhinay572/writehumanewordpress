import { NextResponse } from 'next/server';
import prisma from '@/lib/db';
import { verifyPluginKey } from '@/lib/auth';

/**
 * POST /api/track — called by WordPress plugin after every humanization
 *
 * Body: {
 *   user_email, user_name, user_role, wp_user_id,
 *   site_url, site_name, wp_version, plugin_version, php_version,
 *   input_words, output_words, mode, tone,
 *   post_id, post_title, ip_address, user_agent
 * }
 */
export async function POST(request) {
  if (!verifyPluginKey(request)) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }

  try {
    const body = await request.json();

    const {
      user_email,
      user_name,
      user_role,
      wp_user_id,
      site_url,
      site_name,
      wp_version,
      plugin_version,
      php_version,
      input_words,
      output_words,
      mode,
      tone,
      post_id,
      post_title,
      ip_address,
      user_agent,
    } = body;

    if (!user_email || !site_url || !input_words) {
      return NextResponse.json({ error: 'Missing required fields' }, { status: 400 });
    }

    const now = new Date();
    const firstOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);

    // Upsert user — create if new, update if exists
    const user = await prisma.user.upsert({
      where: {
        email_siteUrl: { email: user_email, siteUrl: site_url },
      },
      update: {
        displayName: user_name || undefined,
        wpRole: user_role || undefined,
        wpUserId: wp_user_id ? parseInt(wp_user_id) : undefined,
        lastActiveAt: now,
        totalWords: { increment: parseInt(input_words) },
        totalRequests: { increment: 1 },
        // Reset monthly counter if new month
        wordsThisMonth: {
          increment: parseInt(input_words),
        },
      },
      create: {
        email: user_email,
        displayName: user_name || null,
        wpUserId: wp_user_id ? parseInt(wp_user_id) : null,
        wpRole: user_role || null,
        siteUrl: site_url,
        totalWords: parseInt(input_words),
        totalRequests: 1,
        wordsThisMonth: parseInt(input_words),
        monthResetAt: firstOfMonth,
      },
    });

    // Reset monthly counter if it's a new month
    if (user.monthResetAt < firstOfMonth) {
      await prisma.user.update({
        where: { id: user.id },
        data: {
          wordsThisMonth: parseInt(input_words),
          monthResetAt: firstOfMonth,
        },
      });
    }

    // Log the request
    await prisma.usageLog.create({
      data: {
        userId: user.id,
        siteUrl: site_url,
        inputWords: parseInt(input_words),
        outputWords: parseInt(output_words) || 0,
        mode: mode || 'balanced',
        tone: tone || null,
        postId: post_id ? parseInt(post_id) : null,
        postTitle: post_title || null,
        ipAddress: ip_address || null,
        userAgent: user_agent || null,
      },
    });

    // Upsert site info
    await prisma.site.upsert({
      where: { siteUrl: site_url },
      update: {
        siteName: site_name || undefined,
        wpVersion: wp_version || undefined,
        pluginVersion: plugin_version || undefined,
        phpVersion: php_version || undefined,
        lastActiveAt: now,
        totalWords: { increment: parseInt(input_words) },
        totalRequests: { increment: 1 },
      },
      create: {
        siteUrl: site_url,
        siteName: site_name || null,
        wpVersion: wp_version || null,
        pluginVersion: plugin_version || null,
        phpVersion: php_version || null,
        totalWords: parseInt(input_words),
        totalRequests: 1,
      },
    });

    // Update total users count for the site
    const uniqueUsers = await prisma.user.count({
      where: { siteUrl: site_url },
    });
    await prisma.site.update({
      where: { siteUrl: site_url },
      data: { totalUsers: uniqueUsers },
    });

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error('Track error:', error);
    return NextResponse.json({ error: 'Server error' }, { status: 500 });
  }
}
