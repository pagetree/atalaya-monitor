<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Free status monitor for your site or API</title>
  <meta name="description" content="Add a site or an API. atalaya checks the reply and shows up, a warning, or down. A warning is over 2 seconds, or a status that could not be read. Free. No ads.">
  <link rel="icon" href="assets/img/logo-icon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700;800&display=swap">
  <link rel="stylesheet" href="assets/css/site.css">
</head>
<body>
  <?php require __DIR__ . '/includes/home-header.php'; ?>

  <main id="content" tabindex="-1">
    <div class="stage">

      <section class="hero" aria-labelledby="poster">
          <h1 id="poster" class="poster">Is your site or API up right now?</h1>
          <p class="lede">Atalaya is a free status monitor. Add the address. It checks the reply and shows up, a warning, or down. A warning is a reply over 2 seconds, or a status that could not be read. No ads.</p>
          <p class="hero-actions">
            <a class="solid" href="app/#add">Add your first address</a>
            <a class="quiet" href="#check">See what a check does</a>
          </p>
        </section>
        <ul class="floats" role="list" aria-label="Sample region cards">
          <li class="float float-up">
            <div class="float-head">
              <p class="float-region">Frankfurt <span class="float-zone">Europe</span></p>
              <p class="float-status"><span class="dot" aria-hidden="true"></span>Up</p>
            </div>
            <p class="float-ms">142<span class="float-unit">ms</span></p>
            <svg class="spark" viewBox="0 0 200 48" preserveAspectRatio="none" aria-hidden="true" focusable="false">
              <path d="M0 32 L28 30 L50 34 L76 24 L100 28 L124 20 L148 26 L172 18 L200 22 L200 48 L0 48 Z"/>
              <path d="M0 32 L28 30 L50 34 L76 24 L100 28 L124 20 L148 26 L172 18 L200 22" fill="none"/>
            </svg>
            <p class="float-uptime"><span>24h uptime</span><strong>99.98%</strong></p>
          </li>
          <li class="float float-slow">
            <div class="float-head">
              <p class="float-region">Singapore <span class="float-zone">Asia</span></p>
              <p class="float-status"><span class="dot" aria-hidden="true"></span>Warning</p>
            </div>
            <p class="float-ms">2408<span class="float-unit">ms</span></p>
            <svg class="spark" viewBox="0 0 200 48" preserveAspectRatio="none" aria-hidden="true" focusable="false">
              <path d="M0 38 L32 36 L60 30 L92 22 L124 14 L156 8 L200 4 L200 48 L0 48 Z"/>
              <path d="M0 38 L32 36 L60 30 L92 22 L124 14 L156 8 L200 4" fill="none"/>
            </svg>
            <p class="float-uptime"><span>24h uptime</span><strong>98.12%</strong></p>
          </li>
          <li class="float float-down">
            <div class="float-head">
              <p class="float-region">Cape Town <span class="float-zone">Africa</span></p>
              <p class="float-status"><span class="dot" aria-hidden="true"></span>Down</p>
            </div>
            <p class="float-ms float-ms-mute">No reply</p>
            <svg class="spark" viewBox="0 0 200 48" preserveAspectRatio="none" aria-hidden="true" focusable="false">
              <path d="M0 16 L36 18 L70 14 L104 18 L132 22 L160 34 L200 44 L200 48 L0 48 Z"/>
              <path d="M0 16 L36 18 L70 14 L104 18 L132 22 L160 34 L200 44" fill="none"/>
            </svg>
            <p class="float-uptime"><span>24h uptime</span><strong>94.20%</strong></p>
          </li>
          <li class="float float-wait">
            <div class="float-head">
              <p class="float-region">Sydney <span class="float-zone">Oceania</span></p>
              <p class="float-status"><span class="dot" aria-hidden="true"></span>Waiting</p>
            </div>
            <p class="float-ms">318<span class="float-unit">ms</span></p>
            <svg class="spark" viewBox="0 0 200 48" preserveAspectRatio="none" aria-hidden="true" focusable="false">
              <path d="M0 28 L40 26 L80 32 L120 24 L160 28 L200 26 L200 48 L0 48 Z"/>
              <path d="M0 28 L40 26 L80 32 L120 24 L160 28 L200 26" fill="none"/>
            </svg>
            <p class="float-uptime"><span>24h uptime</span><strong>—</strong></p>
          </li>
        </ul>
    </div>

      <section class="split" id="check" aria-label="What a check does">
        <div class="split-col">
          <h2><svg class="ico" viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="5.5"/><path d="M8 5v3.2l2.2 1.4"/></svg>What a check does</h2>
          <ol class="steps">
            <li>Name the site or API, paste the address, and choose how often.</li>
            <li>Every minute, or every 5, 15, or 30 minutes, or once an hour.</li>
            <li>atalaya requests that address and times the reply.</li>
            <li>Up means it answered. A warning means the reply took more than 2 seconds, or the status could not be read. Down means it did not answer.</li>
          </ol>
        </div>
        <div class="split-col">
          <h2><svg class="ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M2 4h12M2 8h12M2 12h12"/></svg>Where you look</h2>
          <ul class="board-links">
            <li>
              <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 2v12M2 8h12"/></svg>
              <a href="app/#add">Add Monitor</a>
            </li>
            <li>
              <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 2.2a3.6 3.6 0 0 1 3.6 3.6v2.2l1.2 2H3.2l1.2-2V5.8A3.6 3.6 0 0 1 8 2.2z"/><path d="M6.6 12.2a1.5 1.5 0 0 0 2.8 0"/></svg>
              <a href="app/#alerts">Alerts</a>
            </li>
            <li>
              <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M4 2.5h6.2L13 5.3V13.5H4z"/><path d="M10 2.5V5.5h3M6 8h4M6 10.5h4"/></svg>
              <a href="app/#log">Log</a>
            </li>
            <li>
              <svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="2.1"/><path d="M8 1.7v1.7M8 12.6v1.7M1.7 8h1.7M12.6 8h1.7M3.4 3.4l1.2 1.2M11.4 11.4l1.2 1.2M12.6 3.4l-1.2 1.2M4.6 11.4l-1.2 1.2"/></svg>
              <a href="app/#settings">Settings</a>
            </li>
          </ul>
        </div>
        <aside class="split-col note">
          <h2><svg class="ico" viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="5.5"/><path d="M8 7.2V11.2M8 5h.01"/></svg>What you get</h2>
          <p>You see the failure on your board before a user has to tell you.</p>
          <hr>
          <p>No ads. Free.</p>
        </aside>
      </section>

      <section class="ask" aria-labelledby="ask-title">
        <h2 id="ask-title">Open the board and add the first address.</h2>
        <a class="solid" href="app/">Open the board</a>
      </section>

      <section class="places" aria-labelledby="places-title">
        <h2 id="places-title">Is it up where your users are?</h2>
        <p class="places-sub">Atalaya checks the same address from every region below. A reply in one place can still be a failure in another.</p>
        <ul class="meridian">
          <li>
            <p class="station-zone">Europe</p>
            <p class="station-name">Frankfurt</p>
          </li>
          <li>
            <p class="station-zone">Africa</p>
            <p class="station-name">Cape Town</p>
          </li>
          <li>
            <p class="station-zone">Asia</p>
            <p class="station-name">Singapore</p>
          </li>
          <li>
            <p class="station-zone">Oceania</p>
            <p class="station-name">Sydney</p>
          </li>
        </ul>
        <p class="places-more">More regions are coming!</p>
      </section>
  </main>

  <footer>
    <div class="floor">
      <div class="floor-id">
        <a class="brand floor-brand" href="#top">
          <img class="pulse" src="assets/img/logo-icon.svg" alt="" width="40" height="29">
          <span class="mark-reveal">
            <span class="mark-clip">
              <img class="wordmark" src="assets/img/logo-wordmark.svg" alt="atalaya" width="84" height="29">
            </span>
          </span>
        </a>
        <p>Checks a site or an API and shows you when it is down.</p>
      </div>
      <nav class="floor-nav" aria-label="Footer">
        <a href="app/">The board</a>
        <a href="app/#add">Add Monitor</a>
        <a href="app/#alerts">Alerts</a>
        <a href="app/#log">Log</a>
        <a href="app/#settings">Settings</a>
      </nav>
      <p class="floor-meta">Free. No ads.</p>
    </div>
  </footer>
</body>
</html>
