<section id="view-add" hidden>
  <form id="add-form" class="sheet-card" novalidate>
    <section class="add-block add-identity">
      <div class="identity-grid">
        <label class="identity-address">
          <span class="lab">Address</span>
          <input name="url" type="url" inputmode="url" maxlength="2000" spellcheck="false" placeholder="https://" autocomplete="url" required>
        </label>
        <label class="identity-name">
          <span class="lab">Name</span>
          <input name="name" maxlength="80" autocomplete="off" placeholder="Name on the board">
        </label>
      </div>
    </section>

    <section class="add-block">
      <div class="add-block-head">
        <h2>Type</h2>
        <p>This is the Type column on the board. It also decides what counts as up.</p>
      </div>
      <fieldset class="every" aria-label="Monitor type">
        <div class="kind-list">
          <label class="every-opt kind-opt">
            <input type="radio" name="kind" value="website" checked>
            <span class="every-main">Website</span>
            <span class="every-sub">A 2xx reply counts as up.</span>
          </label>
          <label class="every-opt kind-opt">
            <input type="radio" name="kind" value="api">
            <span class="every-main">API</span>
            <span class="every-sub">A web page reply is a warning.</span>
          </label>
          <label class="every-opt kind-opt">
            <input type="radio" name="kind" value="keyword">
            <span class="every-main">Keyword</span>
            <span class="every-sub">The reply must contain a phrase.</span>
          </label>
          <label class="every-opt kind-opt">
            <input type="radio" name="kind" value="port">
            <span class="every-main">Port</span>
            <span class="every-sub">An open port counts as up.</span>
          </label>
        </div>
      </fieldset>
      <label class="phrase-field" id="exclude-field">
        <span class="lab">Excluded codes</span>
        <input name="exclude" maxlength="80" inputmode="numeric" autocomplete="off" placeholder="404, 503" spellcheck="false">
      </label>
      <label class="phrase-field" id="phrase-field" hidden>
        <span class="lab">Phrase</span>
        <input name="phrase" maxlength="120" autocomplete="off" placeholder="Words that must appear in the reply">
      </label>
      <label class="phrase-field" id="port-field" hidden>
        <span class="lab">Port</span>
        <input name="port" type="number" inputmode="numeric" min="1" max="65535" step="1" placeholder="443">
      </label>
    </section>

    <section class="add-block">
      <div class="add-block-head">
        <h2>How often</h2>
        <p>How many times a day we request this address.</p>
      </div>
      <fieldset class="every" aria-label="Check every">
        <div class="cadence-rail">
          <label class="every-opt"><input type="radio" name="interval" value="60000"><span class="every-main">1 min</span><span class="every-sub">1,440 a day</span></label>
          <label class="every-opt"><input type="radio" name="interval" value="300000" checked><span class="every-main">5 min</span><span class="every-sub">288 a day</span></label>
          <label class="every-opt"><input type="radio" name="interval" value="900000"><span class="every-main">15 min</span><span class="every-sub">96 a day</span></label>
          <label class="every-opt"><input type="radio" name="interval" value="1800000"><span class="every-main">30 min</span><span class="every-sub">48 a day</span></label>
          <label class="every-opt"><input type="radio" name="interval" value="3600000"><span class="every-main">1 hour</span><span class="every-sub">24 a day</span></label>
        </div>
      </fieldset>
    </section>

    <section class="add-block add-places">
      <div class="add-block-head">
        <h2>Where to check</h2>
        <p>Pick one or more regions. A reply in one place can still fail in another.</p>
      </div>
      <fieldset class="every" aria-label="Regions">
        <div class="region-grid">
          <label class="every-opt"><input type="checkbox" name="region" value="frankfurt" checked><span class="every-sub">Europe</span><span class="every-main">Frankfurt</span></label>
          <label class="every-opt"><input type="checkbox" name="region" value="capetown"><span class="every-sub">Africa</span><span class="every-main">Cape Town</span></label>
          <label class="every-opt"><input type="checkbox" name="region" value="singapore"><span class="every-sub">Asia</span><span class="every-main">Singapore</span></label>
          <label class="every-opt"><input type="checkbox" name="region" value="sydney"><span class="every-sub">Oceania</span><span class="every-main">Sydney</span></label>
        </div>
      </fieldset>
    </section>

    <p id="form-error" class="form-error" role="alert" hidden></p>
    <div class="sheet-foot">
      <label class="notify-line">
        <input type="checkbox" name="notify" value="email" checked>
        <span class="notify-copy">Email <em><?= htmlspecialchars((string) ($appUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></em></span>
      </label>
      <p class="foot-note">The regions you picked run the check. This page only shows the result.</p>
      <div class="sheet-actions">
        <a class="btn-quiet" href="#monitors">Cancel</a>
        <button type="submit" class="btn-ink">Add monitor</button>
      </div>
    </div>
  </form>
</section>
