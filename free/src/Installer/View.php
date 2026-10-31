<?php
/**
 * Installer view helpers.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Installer;

/**
 * Small HTML helpers used by the installer page.
 */
final class View {

	/**
	 * Escape for HTML.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public static function e( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}

	/**
	 * Render the checklist returned by {@see Env::check()}.
	 *
	 * @param array<int, array{label: string, ok: bool, detail: string}> $checks Checks.
	 * @return string
	 */
	public static function checks( array $checks ): string {
		$out = '<ul class="checks">';

		foreach ( $checks as $check ) {
			$out .= sprintf(
				'<li class="%s"><span class="badge">%s</span><strong>%s</strong><em>%s</em></li>',
				$check['ok'] ? 'ok' : 'fail',
				$check['ok'] ? '&#10003;' : '&#10007;',
				self::e( $check['label'] ),
				self::e( $check['detail'] )
			);
		}

		return $out . '</ul>';
	}

	/**
	 * Wrap content in the page shell.
	 *
	 * @param string $payload_json JSON payload for the client script.
	 * @param string $body         Body HTML.
	 * @return string
	 */
	public static function page( string $payload_json, string $body ): string {
		$title = 'Backupino Installer';

		return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>' . self::e( $title ) . '</title>
<style>
:root{--bg:#0f172a;--card:#ffffff;--ink:#111827;--muted:#6b7280;--accent:#2563eb;--ok:#16a34a;--fail:#dc2626;--line:#e5e7eb}
*{box-sizing:border-box}
body{margin:0;font:15px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Tahoma,sans-serif;background:linear-gradient(160deg,#eef2ff,#f8fafc 45%);color:var(--ink);min-height:100vh}
.wrap{max-width:760px;margin:0 auto;padding:40px 20px}
.card{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 18px 40px -24px rgba(15,23,42,.45);padding:28px}
h1{margin:0 0 4px;font-size:22px}
p.lead{margin:0 0 22px;color:var(--muted)}
.steps{display:flex;gap:8px;margin:0 0 22px;flex-wrap:wrap}
.steps span{font-size:12px;padding:4px 10px;border-radius:999px;background:#f1f5f9;color:var(--muted)}
.steps span.active{background:var(--accent);color:#fff}
.checks{list-style:none;margin:0;padding:0}
.checks li{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px dashed var(--line);font-size:14px}
.checks li:last-child{border-bottom:0}
.checks li em{margin-left:auto;color:var(--muted);font-style:normal;font-size:12px;max-width:45%;text-align:right;word-break:break-all}
.badge{width:20px;height:20px;border-radius:999px;display:grid;place-items:center;color:#fff;font-size:12px}
li.ok .badge{background:var(--ok)} li.fail .badge{background:var(--fail)}
label{display:block;font-weight:600;font-size:13px;margin:12px 0 4px}
input,select{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:9px;font:inherit;background:#fff}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
button{margin-top:18px;background:var(--accent);color:#fff;border:0;border-radius:9px;padding:11px 18px;font-weight:700;cursor:pointer;font-size:14px}
button[disabled]{opacity:.5;cursor:not-allowed}
button.ghost{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.bar{height:9px;background:#e2e8f0;border-radius:999px;overflow:hidden;margin:14px 0}
.bar i{display:block;height:100%;width:0;background:var(--accent);transition:width .25s}
pre#log{margin:0;background:#0b1220;color:#c7d2fe;border-radius:10px;padding:12px;font-size:12px;max-height:180px;overflow:auto;white-space:pre-wrap}
.hidden{display:none}
.err{color:var(--fail);font-size:13px;min-height:18px}
small.hint{display:block;color:var(--muted);font-size:12px;margin-top:6px}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1>Backupino</h1>
    <p class="lead">Restoring your site &mdash; step by step.</p>
    <div class="steps" id="steps">
      <span data-step="1">1. Environment</span>
      <span data-step="2">2. Database</span>
      <span data-step="3">3. Files</span>
      <span data-step="4">4. Database import</span>
      <span data-step="5">5. URLs</span>
      <span data-step="6">6. Config</span>
    </div>
' . $body . '
  </div>
</div>
<script>window.BACKUPINO_INSTALLER = ' . $payload_json . ';</script>
</body>
</html>';
	}
}
