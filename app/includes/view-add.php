<section id="view-add" hidden>
  <form id="add-form" class="sheet-card" novalidate>
    <section class="add-section">
      <div class="add-copy">
        <h2>What to watch</h2>
        <p>The address we request, and the name on the board. Each check is a GET that follows redirects.</p>
      </div>
      <div class="add-fields">
        <label>
          <span class="lab">Address</span>
          <input name="url" type="url" inputmode="url" maxlength="2000" spellcheck="false" placeholder="https://" autocomplete="url" required>
        </label>
        <label>
          <span class="lab">Name</span>
          <input name="name" maxlength="80" autocomplete="off" placeholder="The name you want on the board">
        </label>
      </div>
    </section>

    <section class="add-section">
      <div class="add-copy">
        <h2>How often</h2>
        <p>The check runs in this browser, on the cadence you pick here.</p>
      </div>
      <fieldset class="every">
        <legend class="lab">Check every</legend>
        <div class="every-grid">
          <label class="every-opt"><input type="radio" name="interval" value="60000"><span class="every-main">1 min</span><span class="every-sub">1,440 a day</span></label>
          <label class="every-opt"><input type="radio" name="interval" value="300000" checked><span class="every-main">5 min</span><span class="every-sub">288 a day</span></label>
          <label class="every-opt"><input type="radio" name="interval" value="900000"><span class="every-main">15 min</span><span class="every-sub">96 a day</span></label>
          <label class="every-opt"><input type="radio" name="interval" value="1800000"><span class="every-main">30 min</span><span class="every-sub">48 a day</span></label>
          <label class="every-opt"><input type="radio" name="interval" value="3600000"><span class="every-main">1 hour</span><span class="every-sub">24 a day</span></label>
        </div>
      </fieldset>
    </section>

    <section class="add-section">
      <div class="add-copy">
        <h2>Where to check</h2>
        <p>Pick one or more regions. A reply in one place can still fail in another.</p>
      </div>
      <fieldset class="every">
        <legend class="lab">Regions</legend>
        <div class="every-grid region-grid">
          <label class="every-opt"><input type="checkbox" name="region" value="frankfurt" checked><span class="every-main">Frankfurt</span><span class="every-sub">Europe</span></label>
          <label class="every-opt"><input type="checkbox" name="region" value="capetown" checked><span class="every-main">Cape Town</span><span class="every-sub">Africa</span></label>
          <label class="every-opt"><input type="checkbox" name="region" value="singapore" checked><span class="every-main">Singapore</span><span class="every-sub">Asia</span></label>
          <label class="every-opt"><input type="checkbox" name="region" value="sydney" checked><span class="every-main">Sydney</span><span class="every-sub">Oceania</span></label>
        </div>
      </fieldset>
    </section>

    <section class="add-section">
      <div class="add-copy">
        <h2>How to notify</h2>
        <p>Where we send a note when a check goes down. Email is the only path for now.</p>
      </div>
      <fieldset class="every">
        <legend class="lab">On an incident</legend>
        <div class="every-grid notify-grid">
          <label class="every-opt"><input type="checkbox" name="notify" value="email" checked><span class="every-main">Email</span><span class="every-sub"><?= htmlspecialchars((string) ($appUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></label>
        </div>
      </fieldset>
    </section>

    <section class="add-section">
      <div class="add-copy">
        <h2>When it warns</h2>
        <p>These rules decide a warning. A missing reply is a down check.</p>
      </div>
      <ul class="add-rules">
        <li>
          <span class="dot slow"></span>
          <span><strong>Slow reply</strong>Anything over 2 seconds.</span>
        </li>
        <li>
          <span class="dot down"></span>
          <span><strong>Unreadable status</strong>A status code that could not be read.</span>
        </li>
      </ul>
    </section>

    <p id="form-error" class="form-error" role="alert" hidden></p>
    <div class="sheet-foot">
      <p>The first check starts as soon as you save it.</p>
      <div class="sheet-actions">
        <a class="btn-quiet" href="#monitors">Cancel</a>
        <button type="submit" class="btn-ink">Add monitor</button>
      </div>
    </div>
  </form>
</section>
