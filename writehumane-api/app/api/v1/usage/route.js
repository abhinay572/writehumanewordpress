import { NextResponse } from 'next/server';
import { authenticateApiKey, authenticateJWT, PLAN_LIMITS } from '@/lib/auth';
import prisma from '@/lib/db';

export async function GET(request) {
  try {
    // Try both auth methods
    let user = await authenticateJWT(request);
    if (!user) {
      user = await authenticateApiKey(request);
    }
    if (!user) {
      return NextResponse.json({ success: false, error: 'Unauthorized' }, { status: 401 });
    }

    const planLimits = PLAN_LIMITS[user.plan] || PLAN_LIMITS.FREE;

    // Total all-time stats
    const totals = await prisma.usageLog.aggregate({
      where: { userId: user.id, success: true },
      _sum: { inputWords: true, outputWords: true },
      _count: true,
    });

    // Recent requests
    const recentRequests = await prisma.usageLog.findMany({
      where: { userId: user.id },
      orderBy: { createdAt: 'desc' },
      take: 10,
    });

    return NextResponse.json({
      success: true,
      plan: {
        name: planLimits.name,
        tier: user.plan,
        price: planLimits.price,
        monthlyWordLimit: planLimits.monthlyWords,
        ratePerMinute: planLimits.ratePerMinute,
      },
      usage: {
        wordsUsedThisMonth: user.wordsUsedThisMonth,
        wordsRemaining: Math.max(0, planLimits.monthlyWords - user.wordsUsedThisMonth),
        monthlyLimit: planLimits.monthlyWords,
        creditResetAt: user.creditResetAt,
      },
      totals: {
        totalRequests: totals._count,
        totalInputWords: totals._sum.inputWords || 0,
        totalOutputWords: totals._sum.outputWords || 0,
      },
      recent_requests: recentRequests.map((r) => ({
        id: r.id,
        inputWords: r.inputWords,
        outputWords: r.outputWords,
        mode: r.mode,
        provider: r.provider,
        model: r.model,
        latencyMs: r.latencyMs,
        success: r.success,
        errorMsg: r.errorMsg,
        createdAt: r.createdAt,
      })),
    });
  } catch (error) {
    console.error('Usage error:', error);
    return NextResponse.json({ success: false, error: 'Server error.' }, { status: 500 });
  }
}
