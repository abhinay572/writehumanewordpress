import { NextResponse } from 'next/server';
import { authenticateApiKey, checkWordBudget, deductWords, PLAN_LIMITS } from '@/lib/auth';
import { humanize, countWords } from '@/lib/humanizer';
import { checkRateLimit } from '@/lib/rate-limit';
import prisma from '@/lib/db';

export async function GET() {
  return NextResponse.json({
    service: 'WriteHumane AI Humanizer',
    version: '1.0.0',
    status: 'operational',
  });
}

export async function POST(request) {
  try {
    // Authenticate
    const user = await authenticateApiKey(request);
    if (!user) {
      return NextResponse.json(
        { success: false, error: 'Invalid or missing API key. Include Authorization: Bearer wh_live_xxx header.' },
        { status: 401 }
      );
    }

    // Rate limit
    const planLimits = PLAN_LIMITS[user.plan] || PLAN_LIMITS.FREE;
    const rateResult = checkRateLimit(user.id, planLimits.ratePerMinute);
    if (!rateResult.allowed) {
      return NextResponse.json(
        { success: false, error: 'Rate limit exceeded. Please slow down.', retry_after: Math.ceil((rateResult.resetAt - Date.now()) / 1000) },
        { status: 429 }
      );
    }

    // Parse body
    const body = await request.json().catch(() => null);
    if (!body || !body.text || typeof body.text !== 'string' || !body.text.trim()) {
      return NextResponse.json(
        { success: false, error: 'Missing or empty "text" field in request body.' },
        { status: 400 }
      );
    }

    const text = body.text.trim();
    const mode = ['light', 'balanced', 'aggressive'].includes(body.mode) ? body.mode : 'balanced';
    const tone = ['professional', 'casual', 'academic', 'friendly'].includes(body.tone) ? body.tone : 'professional';
    const preserveKeywords = body.preserve_keywords !== false;

    // Check word count
    const wordCount = countWords(text);
    if (wordCount > 5000) {
      return NextResponse.json(
        { success: false, error: `Text exceeds 5,000 word limit. Your text has ${wordCount} words.` },
        { status: 400 }
      );
    }

    // Check word budget
    const hasbudget = await checkWordBudget(user, wordCount);
    if (!hasbudget) {
      const remaining = planLimits.monthlyWords - user.wordsUsedThisMonth;
      return NextResponse.json(
        { success: false, error: `Monthly word limit exceeded. You have ${Math.max(0, remaining)} words remaining. Upgrade your plan at https://writehumane.com/pricing` },
        { status: 402 }
      );
    }

    // Humanize
    const result = await humanize(text, { mode, tone, preserve_keywords: preserveKeywords });

    // Deduct words
    await deductWords(user.id, result.original_length);

    // Log usage
    const ip = request.headers.get('x-forwarded-for') || request.headers.get('x-real-ip') || 'unknown';
    await prisma.usageLog.create({
      data: {
        userId: user.id,
        inputWords: result.original_length,
        outputWords: result.humanized_length,
        mode,
        provider: result.provider,
        model: result.model,
        latencyMs: result.latencyMs,
        success: true,
        ipAddress: ip,
      },
    });

    // Refresh user for accurate remaining count
    const updatedUser = await prisma.user.findUnique({ where: { id: user.id } });
    const creditsRemaining = planLimits.monthlyWords - updatedUser.wordsUsedThisMonth;

    const responseData = {
      success: true,
      humanized_text: result.humanized_text,
      data: result.humanized_text,
      result: result.humanized_text,
      original_length: result.original_length,
      humanized_length: result.humanized_length,
      detection_score: result.detection_score,
      credits_used: result.original_length,
      credits_remaining: Math.max(0, creditsRemaining),
      processing_time: result.latencyMs,
    };

    return NextResponse.json(responseData);
  } catch (error) {
    console.error('Humanize error:', error);

    // Try to log the error
    try {
      const user = await authenticateApiKey(request);
      if (user) {
        await prisma.usageLog.create({
          data: {
            userId: user.id,
            inputWords: 0,
            outputWords: 0,
            mode: 'unknown',
            provider: 'unknown',
            success: false,
            errorMsg: error.message,
          },
        });
      }
    } catch {
      // Ignore logging errors
    }

    return NextResponse.json(
      { success: false, error: 'Internal server error. Please try again later.' },
      { status: 500 }
    );
  }
}
