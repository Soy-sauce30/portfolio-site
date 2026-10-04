<!-- HUD theme only: live clock + local weather (filled in by /cars/hud.js) -->
  <div class="hud-bar hud-only">
    <div class="hud-motto">Search <i>/</i> Compare <i>/</i> Explore</div>
    <div class="hud-meta">
      <div class="hud-clock">
        <time class="hud-time" id="hudTime"></time>
        <span class="hud-date" id="hudDate"></span>
      </div>
      <div class="hud-weather" id="hudWeather">
        <button type="button" class="hud-weather-btn" id="hudWeatherBtn" hidden>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/></svg>
          Show local weather
        </button>
        <span class="hud-weather-note" id="hudWeatherNote" hidden></span>
        <div class="hud-weather-now" id="hudWeatherNow" hidden>
          <span class="hud-weather-icon" id="hudWeatherIcon" aria-hidden="true"></span>
          <span class="hud-temp" id="hudTemp"></span>
          <span class="hud-cond" id="hudCond"></span>
        </div>
      </div>
    </div>
  </div>
