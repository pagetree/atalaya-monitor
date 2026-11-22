<div id="add-scrim" class="scrim" data-action="close-add"></div>
<aside id="add" class="drawer" role="dialog" aria-modal="true" aria-labelledby="add-title" aria-hidden="true" inert>
  <div class="drawer-head">
    <h2 id="add-title">Add a monitor</h2>
    <button type="button" class="drawer-x" data-action="close-add" aria-label="Close"><svg class="ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M4 4l8 8M12 4l-8 8"/></svg></button>
  </div>
  <form id="add-form" novalidate>
    <div class="add-form">
      <p class="drawer-lead">A name, the address to check, and how often.</p>
      <label><span class="lab">Name</span>
        <input name="name" maxlength="80" autocomplete="off">
      </label>
      <label><span class="lab">Address</span>
        <input name="url" type="url" inputmode="url" maxlength="2000" spellcheck="false" placeholder="https://" autocomplete="url" required>
      </label>
      <fieldset class="every">
        <legend class="lab">Check every</legend>
        <div class="every-grid">
          <label class="every-opt"><input type="radio" name="interval" value="60000"><span>1 min</span></label>
          <label class="every-opt"><input type="radio" name="interval" value="300000" checked><span>5 min</span></label>
          <label class="every-opt"><input type="radio" name="interval" value="900000"><span>15 min</span></label>
          <label class="every-opt"><input type="radio" name="interval" value="1800000"><span>30 min</span></label>
          <label class="every-opt"><input type="radio" name="interval" value="3600000"><span>1 hour</span></label>
        </div>
      </fieldset>
      <p class="hint">Warning means a reply over 2 seconds, or a status code that could not be read.</p>
      <p id="form-error" class="form-error" role="alert" hidden></p>
      <button type="submit" class="solid">Add monitor</button>
    </div>
  </form>
</aside>
