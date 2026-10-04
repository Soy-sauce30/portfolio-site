/* =========================================
   Spec explainer — tap any spec to see what it means.
   Mark an element with data-explain="Label" (and
   optionally data-value="6-cyl 3.0L turbo") and it
   becomes clickable. Used on the homepage and All Cars.
   ========================================= */
(function () {
  // ---------- What each spec means ----------
  var GLOSSARY = {
    'engine': ['Engine', 'The part that makes the car go. Gas engines burn fuel inside cylinders; the number of cylinders and their total size (in liters) roughly tell you how powerful and thirsty it is. Electric cars use motors instead.'],
    'transmission': ['Transmission', 'Sends the engine’s power to the wheels through a set of gears — low gears for pulling away, high gears for cruising. More gears usually means smoother and more efficient driving.'],
    'drivetrain': ['Drivetrain', 'Which wheels the engine actually pushes. FWD = front wheels, RWD = rear wheels, AWD/4WD = all four for extra grip in rain, snow or off-road.'],
    'drive': ['Drivetrain', 'Which wheels the engine actually pushes. FWD = front wheels, RWD = rear wheels, AWD/4WD = all four for extra grip in rain, snow or off-road.'],
    'fuel economy': ['Fuel economy (mpg)', 'Miles per gallon — how far the car goes on one gallon of fuel. Higher is better. “City” is stop-and-go driving, “hwy” is steady highway speeds, and “combined” is a 55/45 mix of the two. These are official EPA test numbers.'],
    'mpg': ['MPG (miles per gallon)', 'How far the car goes on one gallon of fuel. Higher is better — 30 mpg uses half the fuel of 15 mpg over the same distance. When you see a range like 26–31, it’s the lowest and highest of this model’s versions.'],
    'efficiency': ['MPGe (efficiency)', '“Miles per gallon equivalent” — how far an electric car goes on the same energy as one gallon of gas (33.7 kWh). It lets you compare EVs with gas cars: most EVs land around 80–130 MPGe, versus 25–50 mpg for gas cars.'],
    'mpge': ['MPGe', '“Miles per gallon equivalent” — how far an electric car goes on the same energy as one gallon of gas (33.7 kWh). Higher means it uses less electricity per mile.'],
    'epa range': ['EPA range', 'How many miles an electric car can go on a full charge, from the EPA’s official test. Real-world range drops in cold weather, at high highway speeds, and with heavy use of heat or A/C.'],
    'range': ['Range', 'How many miles an electric car can go on a full charge (EPA estimate). Cold weather and fast highway driving usually lower it.'],
    'fuel': ['Fuel', 'What the car runs on. “Regular” is 87-octane gas, “Premium” is 91+ octane (more expensive, needed by many high-performance engines), and “Electricity” means it plugs in.'],
    'class': ['Class', 'The EPA’s size category. Cars are grouped by passenger + cargo space inside (Minicompact → Large), while SUVs and trucks are grouped by gross vehicle weight rating (their maximum loaded weight). It’s useful for comparing similar-sized vehicles.'],
    'yearly fuel cost': ['Yearly fuel cost', 'The EPA’s estimate of what you’d spend on gas or electricity in a year, assuming 15,000 miles of driving (55% city, 45% highway) at average national prices. Your cost depends on how much you drive and local prices.'],
    'epa fuel score': ['EPA fuel score', 'A 1–10 rating of fuel economy that appears on every new car’s window sticker. 10 is the most efficient, 1 the least. Most gas cars score around 4–6; EVs usually score 10.'],
    'co₂': ['CO₂ (carbon dioxide)', 'How many grams of CO₂ — the main greenhouse gas — come out of the tailpipe per mile. Lower is better for the climate. Electric cars have zero tailpipe emissions.'],
    'co2': ['CO₂ (carbon dioxide)', 'How many grams of CO₂ — the main greenhouse gas — come out of the tailpipe per mile. Lower is better for the climate.'],
    'new price': ['New price (MSRP)', 'MSRP is the “Manufacturer’s Suggested Retail Price” — the sticker price. The range runs from the cheapest trim to the most expensive one. It doesn’t include taxes, the destination fee (usually $1,000–$2,000) or any dealer markup, and prices here are approximate.'],
    'price': ['Price (MSRP)', 'The approximate sticker price for a new one. “~$55k” is this exact version; a range with a model name under it covers that whole model, from the base trim to the top trim. Taxes, fees and dealer markups are extra.'],
    'used': ['Used price', 'What people typically pay for one second-hand. It depends heavily on age, mileage, condition and options.'],
    'horsepower': ['Horsepower', 'How much work the engine can do — roughly, how quickly it can accelerate and how fast it can go. A family car has about 200 hp; sports cars 180\u2013700 hp.'],
    'torque': ['Torque', 'The engine\u2019s twisting force — the \u201cshove\u201d you feel when you press the gas. High torque helps with quick starts and towing. Measured in lb-ft.'],
    'seating': ['Seating', 'How many people it can carry with a seat belt for each.'],
    'curb weight': ['Curb weight', 'How much the car weighs empty but ready to drive (full fluids, no people or cargo). Lighter cars feel more nimble and use less energy.'],
    'ev range': ['EV range', 'How many miles an electric car can go on a full charge (EPA estimate). Cold weather and fast highway driving usually lower it.'],
    'cargo space': ['Cargo space', 'How much stuff fits in the trunk or cargo area, in cubic feet. A small car has about 12\u201315 cu ft; a big SUV can have 40+ with the back seats up.'],
    'cargo volume': ['Cargo space', 'How much stuff fits in the trunk or cargo area, in cubic feet. A small car has about 12\u201315 cu ft; a big SUV can have 40+ with the back seats up.'],
    'towing': ['Towing capacity', 'The heaviest trailer the vehicle is rated to pull safely.'],
    'power': ['Power (horsepower)', 'How much work the engine can do — roughly, how fast it can accelerate and how high a top speed it can reach. A family car has about 200 hp; sports cars 180–700 hp; top race cars 700–1,000+ hp.'],
    'top speed': ['Top speed', 'The fastest the car can go on a long enough straight. Race car top speeds depend a lot on the track and how the car’s wings are set up.'],
    '0–60 mph': ['0–60 mph', 'How many seconds it takes to go from a stop to 60 mph — the classic acceleration test. A typical family car takes 7–8 s; supercars do it in under 3 s.'],
    '0–100 mph': ['0–100 mph', 'Seconds from a stop to 100 mph. Top Fuel dragsters do it in under a second — faster than almost anything else on Earth.'],
    'weight': ['Weight', 'How heavy the vehicle is. Lighter cars accelerate, stop and turn more easily; heavier ones feel more planted but use more energy.'],
    'minimum weight': ['Minimum weight', 'The lightest the rules allow the car to be (including the driver). Teams build under it and add ballast to hit it exactly, placing the weight where it helps handling most.'],
    'tires': ['Tires', 'Race tires are made of soft rubber for maximum grip and may only last a few laps. In many series every team must use the same supplier.'],
    'race distance': ['Race distance', 'How far a race covers from start to finish.'],
    'race length': ['Race length', 'How long a race lasts. Endurance races are measured in hours rather than laps.'],
    'chassis': ['Chassis', 'The car’s main frame — the structure everything else bolts to. In “spec” series every team uses the same chassis so racing stays close.'],
    'engine makers': ['Engine makers', 'The companies that build the engines teams can choose from.'],
    'wheels': ['Wheels', 'NASCAR’s Next Gen car uses a single big nut in the center of each wheel instead of five small lug nuts, like most race cars around the world.'],
    'car': ['Car generation', 'Racing series redesign their cars every so often. The generation tells you which rulebook the car is built to.'],
    '1,000 ft time': ['1,000 ft time', 'Top Fuel races run 1,000 feet instead of a quarter mile (1,320 ft) for safety. This is how long a run takes from leaving the starting line to crossing the finish line (reaction time isn’t counted).'],
    'length': ['Length / wheelbase', 'Wheelbase is the distance between the front and rear wheels. A long wheelbase keeps a dragster stable at 300+ mph.'],
    'engine life': ['Engine life', 'How long an engine lasts before it needs rebuilding. Race engines trade durability for enormous power.'],
    'surfaces': ['Surfaces', 'What the car races on. Rally cars change their setup and tires for each surface.'],
    'drivers': ['Drivers', 'How many people share driving one car during a race.'],
    'motors': ['Motors', 'Electric motors drive the wheels and also act as generators when slowing down, turning braking energy back into battery charge.'],
    'regen braking': ['Regenerative braking', 'Slowing the car down by running the motor as a generator, which recharges the battery instead of wasting that energy as heat in the brakes.'],
    'tracks': ['Tracks', 'Where the series races.'],
    'judging': ['Judging', 'Drifting isn’t won on time — judges score how the driver slides: their line through the course, the angle of the car, and style.'],
    'entry speed': ['Entry speed', 'How fast the car is going when it starts sliding into the first corner. Speed isn’t scored on its own, but carrying speed with big angle and commitment helps the style score.'],
    'engines': ['Engines', 'The engine types you’ll see in this series.'],
    'height': ['Height', 'How tall the vehicle is from the ground to the roof.'],
    'jumps': ['Jumps', 'How high and far these trucks can fly off ramps during freestyle runs.'],
    'suspension travel': ['Suspension travel', 'How far the wheels can move up and down. More travel soaks up bigger bumps and jumps without slamming the chassis into the ground.'],
    'signature race': ['Signature race', 'The most famous event this kind of vehicle competes in.'],
    'build cost': ['Build cost', 'Roughly what it costs to build one competitive vehicle.'],
    'hybrid': ['Hybrid', 'Has both a gas engine and an electric motor with a small battery. The battery charges itself while driving and braking — you never plug it in — which saves a lot of fuel in city driving.'],
    'plug-in hybrid': ['Plug-in hybrid', 'A hybrid with a bigger battery you can charge from an outlet. It drives on electricity for the first 20–50 miles or so, then switches to gas like a normal hybrid.'],
    'electric': ['Electric (EV)', 'Runs only on a battery and electric motors — no gas at all. You charge it at home or at public chargers, and it has zero tailpipe emissions.'],
    'diesel': ['Diesel', 'Burns diesel fuel instead of gasoline. Diesels make lots of pulling power (torque) and are efficient on the highway — popular in trucks.'],
    'hydrogen': ['Hydrogen fuel cell', 'Makes its own electricity from hydrogen gas, and the only thing out of the tailpipe is water. Refueling stations are rare.']
  };

  // ---------- What this car's specific value means ----------
  function aboutEngine(v) {
    var out = [];
    if (/electric/i.test(v) && !/hybrid/i.test(v)) out.push('This one is fully electric — power comes from battery-fed motors, not a gas engine.');
    var cyl = v.match(/(\d+)-cyl/i);
    if (cyl) out.push(cyl[1] + ' cylinders: each is a chamber where fuel burns to push a piston. More cylinders usually means smoother, stronger power (and more fuel use).');
    var vee = v.match(/\bV(6|8|10|12)\b/);
    if (vee) out.push('A V' + vee[1] + ' has ' + vee[1] + ' cylinders arranged in two rows shaped like a “V”.');
    var l = v.match(/(\d+(?:\.\d+)?)L/);
    if (l) out.push(l[1] + ' liters is the engine’s size (displacement) — the total volume of all its cylinders. Small cars are around 1.5–2.0 L; big trucks and muscle cars 5–6+ L.');
    var ci = v.match(/(\d+)\s*cu in/i);
    if (/no turbo/i.test(v)) out.push('No turbo (\u201cnaturally aspirated\u201d): the engine breathes normal air pressure — simpler, with instant throttle response.');
    if (ci) out.push(ci[1] + ' cubic inches is about ' + (ci[1] * 0.016387).toFixed(1) + ' liters of engine size.');
    if (/turbo/i.test(v) && !/no turbo/i.test(v)) out.push('Turbo: a fan spun by exhaust gases forces extra air into the engine, so a smaller engine makes big-engine power.');
    if (/supercharg/i.test(v)) out.push('Supercharged: a belt-driven pump forces extra air into the engine for instant extra power.');
    if (/plug-in hybrid/i.test(v)) out.push('Plug-in hybrid: adds a rechargeable battery and electric motor, so short trips can be all-electric.');
    else if (/hybrid/i.test(v)) out.push('Hybrid: an electric motor helps the engine, saving fuel, especially in town.');
    if (/diesel/i.test(v)) out.push('Diesel: burns diesel fuel for strong pulling power and good highway efficiency.');
    if (/kW/.test(v)) out.push('kW (kilowatts) measures electric motor power: 1 kW ≈ 1.34 horsepower.');
    return out;
  }

  function aboutTransmission(v) {
    var out = [], n;
    if ((n = v.match(/AM-?S?(\d+)/i))) out.push(n[1] + '-speed automated manual (dual-clutch): shifts itself super fast, like a race car. The “S” means you can also shift with paddles.');
    else if (/AV|variable/i.test(v)) out.push('CVT (continuously variable): no fixed gears — it smoothly changes ratio to keep the engine at its most efficient speed.');
    else if ((n = v.match(/\(A1\)/))) out.push('Single-speed: electric motors spin so fast they only need one gear.');
    else if ((n = v.match(/S(\d+)/))) out.push(n[1] + '-speed automatic with a manual mode — it shifts for you, but you can pick gears with paddles or the shifter.');
    else if ((n = v.match(/Automatic\s*\(A(\d+)\)/i))) out.push(n[1] + '-speed automatic — it shifts gears for you.');
    if ((n = v.match(/Manual\s*(\d+)/i))) out.push(n[1] + '-speed manual — you shift gears yourself with a clutch pedal and stick. Fun, but rarer every year.');
    if ((n = v.match(/(\d+)-speed sequential/i))) out.push(n[1] + '-speed sequential: gears go strictly in order (1, 2, 3…), which lets race drivers shift in milliseconds.');
    if (/paddle/i.test(v)) out.push('Paddle shift: the driver changes gear with paddles behind the steering wheel.');
    return out;
  }

  function aboutDrive(v) {
    var out = [];
    if (/front|FWD/i.test(v)) out.push('Front-wheel drive: the front wheels pull the car. Cheaper, lighter and good in the rain.');
    if (/rear|RWD/i.test(v)) out.push('Rear-wheel drive: the rear wheels push the car. Better balance for sporty driving, but less grip on snow.');
    if (/all-wheel|all wheel|AWD/i.test(v)) out.push('All-wheel drive: power goes to all four wheels automatically for extra grip in bad weather.');
    if (/4-wheel|4WD|part-time/i.test(v)) out.push('Four-wheel drive: a heavy-duty system for off-roading and towing, often switched on by the driver.');
    if (/2-wheel|2WD/i.test(v) && !/front|rear/i.test(v)) out.push('Two-wheel drive: only one pair of wheels is powered (usually the rear on trucks).');
    return out;
  }

  function aboutFuel(v) {
    if (/premium/i.test(v)) return ['Premium: 91–93 octane gas, which costs about 80¢–$1 more per gallon than regular. High-performance engines need it to avoid “knocking”.'];
    if (/regular/i.test(v)) return ['Regular: ordinary 87-octane gas — the cheapest kind at the pump.'];
    if (/midgrade/i.test(v)) return ['Midgrade: 89-octane gas, between regular and premium.'];
    if (/E85/i.test(v)) return ['E85: 51–83% ethanol (mostly made from corn). This car can run on it or regular gas.'];
    if (/electric/i.test(v)) return ['Electricity: you charge it instead of buying gas.'];
    if (/diesel/i.test(v)) return ['Diesel fuel — sold at most gas stations, usually from a separate (often green) pump.'];
    if (/nitromethane/i.test(v)) return ['Nitromethane carries its own oxygen, so an engine can burn far more of it than gasoline — that’s how Top Fuel makes ~11,000 hp.'];
    if (/sustainable|renewable/i.test(v)) return ['Made from waste, plants or captured carbon instead of crude oil, so it adds far less new CO₂.'];
    return [];
  }

  function aboutMpg(v) {
    var m = v.match(/(\d+)\s*city\s*\/\s*(\d+)\s*hwy/i);
    if (m) return ['This car: ' + m[1] + ' mpg around town, ' + m[2] + ' mpg on the highway. Most cars do better on the highway because they aren\u2019t constantly speeding up and braking \u2014 hybrids often do better in town.'];
    var n = v.match(/(\d+)/);
    if (!n) return [];
    var low = +n[1]; // for a range like "26\u201331", judge the least efficient version
    if (/mpge/i.test(v)) {
      if (low >= 120) return ['Very efficient for an electric car — most EVs get about 80\u2013130 MPGe.'];
      if (low < 80) return ['Less efficient than most electric cars (usually big or heavy ones) — most EVs get about 80\u2013130 MPGe. Still far cheaper per mile than a gas car.'];
      return ['About average for an electric car — most EVs get about 80\u2013130 MPGe.'];
    }
    if (low >= 40) return ['That\u2019s excellent — most new cars get about 25\u201335 mpg.'];
    if (low <= 18) return ['That\u2019s thirsty — most new cars get about 25\u201335 mpg. Big engines, heavy vehicles and fast cars use more fuel.'];
    return [];
  }

  function aboutScore(v) {
    var n = v.match(/(\d+)\s*\/\s*10/);
    if (!n) return [];
    var s = +n[1];
    return [s >= 8 ? 'This car scores ' + s + '/10 — among the most efficient you can buy.'
          : s >= 5 ? 'This car scores ' + s + '/10 — about average.'
          : 'This car scores ' + s + '/10 — thirstier than most, which is common for big or fast vehicles.'];
  }

  function aboutClass(v) {
    var c = v.toLowerCase();
    if (c.indexOf('two seater') > -1) return ['Two Seaters: cars built for just a driver and one passenger — mostly sports cars.'];
    if (c.indexOf('minicompact') > -1) return ['Minicompact: the smallest cars by interior space — often sports coupes with tiny back seats.'];
    if (c.indexOf('pickup') > -1) return ['Pickup truck, sized by gross vehicle weight rating (max loaded weight). “Small” pickups are rated under 6,000 lb.'];
    if (c.indexOf('sport utility') > -1) return ['SUV, sized by gross vehicle weight rating (max loaded weight). “Small” SUVs are rated under 6,000 lb; “Standard” ones 6,000 lb and up.'];
    return [];
  }

  function describe(label, value) {
    var key = (label || '').toLowerCase().replace(/\s*\(.*\)$/, '').replace(/(\d)\s*-\s*(\d)/g, '$1\u2013$2').trim();
    var entry = GLOSSARY[key] || GLOSSARY[key.split(' ')[0]] ||
      (key.indexOf('fuel economy') === 0 ? GLOSSARY['fuel economy'] : null) ||
      (key.indexOf('new price') === 0 ? GLOSSARY['new price'] : null);
    var extra = [];
    if (value) {
      if (/engine/.test(key)) extra = aboutEngine(value);
      else if (/transmission/.test(key)) extra = aboutTransmission(value);
      else if (/drive/.test(key)) extra = aboutDrive(value);
      else if (/^fuel$/.test(key)) extra = aboutFuel(value);
      else if (/fuel economy|mpg|efficiency/.test(key)) extra = aboutMpg(value);
      else if (/fuel score/.test(key)) extra = aboutScore(value);
      else if (/class/.test(key)) extra = aboutClass(value);
    }
    if (!entry && !extra.length) return null;
    return { title: entry ? entry[0] : label, text: entry ? entry[1] : '', extra: extra, value: value };
  }

  // ---------- Popover ----------
  var pop = null, current = null;

  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function close() {
    if (!pop) return;
    pop.remove();
    pop = null;
    if (current) { current.setAttribute('aria-expanded', 'false'); current = null; }
  }

  function open(el) {
    var info = describe(el.dataset.explain, el.dataset.value || '');
    if (!info) return;
    close();
    current = el;
    el.setAttribute('aria-expanded', 'true');

    pop = document.createElement('div');
    pop.className = 'explain-pop';
    pop.setAttribute('role', 'dialog');
    pop.setAttribute('aria-label', info.title);
    pop.innerHTML =
      '<button type="button" class="explain-close" aria-label="Close">&times;</button>' +
      '<div class="explain-title">' + esc(info.title) + '</div>' +
      (info.value ? '<div class="explain-value">' + esc(info.value) + '</div>' : '') +
      (info.text ? '<p>' + esc(info.text) + '</p>' : '') +
      (info.extra.length ? '<div class="explain-extra"><div class="explain-sub">For this car</div><ul>' +
        info.extra.map(function (x) { return '<li>' + esc(x) + '</li>'; }).join('') + '</ul></div>' : '');
    document.body.appendChild(pop);

    place();
    pop.querySelector('.explain-close').focus({ preventScroll: true });
  }

  function sheetMode() { return window.innerWidth <= 600; }

  // Phones get a bottom sheet (via CSS); bigger screens get a popover under the spec.
  function place() {
    if (!pop || !current || sheetMode()) return;
    var r = current.getBoundingClientRect();
    if (!r.width && !r.height) { close(); return; } // the spec was removed or hidden
    var w = pop.offsetWidth;
    var left = Math.min(Math.max(12, r.left + r.width / 2 - w / 2), document.documentElement.clientWidth - w - 12);
    var top = r.bottom + 10;
    if (top + pop.offsetHeight > window.innerHeight - 12 && r.top - pop.offsetHeight - 10 > 12) top = r.top - pop.offsetHeight - 10;
    pop.style.left = left + window.scrollX + 'px';
    pop.style.top = top + window.scrollY + 'px';
  }

  // Content above the spec can load in later (e.g. the 3D viewer) and push it down.
  if (window.ResizeObserver) new ResizeObserver(function () { place(); }).observe(document.body);

  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-explain]');
    if (el) {
      e.preventDefault();   // specs can sit inside a card link — don't navigate
      e.stopPropagation();
      if (el === current) close(); else open(el);
      return;
    }
    if (!pop) return;
    if (e.target.closest('.explain-close')) {
      var back = current;
      close();
      if (back) back.focus({ preventScroll: true });
    } else if (!pop.contains(e.target)) {
      // The sheet covers the page on phones, so a tap outside it means "dismiss" —
      // don't also open whatever was underneath.
      if (sheetMode()) { e.preventDefault(); e.stopPropagation(); }
      close();
    }
  }, true);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && pop) { var back = current; close(); if (back) back.focus(); }
    // Tabbing out of the popup: close it and continue from the spec that opened it
    // (the browser then moves focus to the next/previous element from there).
    if (e.key === 'Tab' && pop && pop.contains(document.activeElement)) {
      var from = current;
      close();
      if (from) from.focus({ preventScroll: true });
    }
    // Keyboard support for specs that aren't native buttons
    if ((e.key === 'Enter' || e.key === ' ') && e.target.matches && e.target.matches('[data-explain]:not(button)')) {
      e.preventDefault();
      open(e.target);
    }
  });

  var lastWidth = window.innerWidth;
  window.addEventListener('resize', function () {
    if (window.innerWidth === lastWidth) return place(); // height-only (phone address bar)
    lastWidth = window.innerWidth;
    close();
  });

  window.Explain = { describe: describe };
})();
