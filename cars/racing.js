/* =========================================
   Race car series for the homepage.
   Figures are approximate — they change with the
   rules each season and vary by team.
   3D models are embedded from Sketchfab; each
   model's author and license is credited on the page.
   ========================================= */
var RACE_CARS = [
  {
    id: 'f1',
    name: 'Formula 1',
    tag: 'Open-wheel',
    type: 'open',
    desc: 'The fastest cars on road courses, built by teams like Ferrari, McLaren and Red Bull.',
    summary: 'Formula 1 is the top level of single-seater racing, with Grands Prix on five continents. Each team designs its own car within strict rules, and the cars generate so much downforce that at speed they could, in theory, drive upside down.',
    specs: [
      ['Engine', '1.6L turbo V6 hybrid'],
      ['Power', '~1,000 hp (about half electric from 2026)'],
      ['Top speed', '~220 mph'],
      ['0–60 mph', '~2.6 s'],
      ['Minimum weight', '~1,690 lb with driver (2026)'],
      ['Fuel', '100% sustainable fuel (2026)'],
      ['Tires', 'Pirelli, 18-inch'],
      ['Race distance', '~190 miles (305 km)']
    ],
    facts: [
      'Drivers pull 5–6 g under hard braking and in fast corners.',
      'A pit stop to change all four tires takes about 2 seconds.',
      'The 3D model is the 2023 Red Bull RB19, which won 21 of 22 races that year.'
    ],
    wiki: 'https://en.wikipedia.org/wiki/Formula_One_car',
    model: { uid: 'e4afe46f3aab4b23a418da06fc163821', name: 'Oracle Red Bull F1 Car RB19 2023', author: 'Redgrund', license: 'CC Attribution',
             thumb: 'https://media.sketchfab.com/models/e4afe46f3aab4b23a418da06fc163821/thumbnails/bc90275decbb4148a04facb0039f3c60/fa1d1c8d38b441a9b40f21d1b3f692b3.jpeg' }
  },
  {
    id: 'indycar',
    name: 'IndyCar',
    tag: 'Open-wheel',
    type: 'open',
    desc: 'America’s open-wheel series and the Indy 500 — the fastest oval racing anywhere.',
    summary: 'IndyCar races on ovals, street circuits and road courses, mostly in the US. Every team uses the same Dallara chassis with a Chevrolet or Honda engine, so races are famously close. Its crown jewel is the Indianapolis 500.',
    specs: [
      ['Engine', '2.2L twin-turbo V6 + hybrid'],
      ['Power', '~700–800 hp with push-to-pass'],
      ['Top speed', '~240 mph at Indianapolis'],
      ['Transmission', '6-speed sequential, paddle shift'],
      ['Chassis', 'Dallara DW12 (every team)'],
      ['Engine makers', 'Chevrolet or Honda'],
      ['Fuel', '100% renewable race fuel']
    ],
    facts: [
      'The Indy 500 winner celebrates by drinking a bottle of milk.',
      'Qualifying laps at Indianapolis average around 230+ mph.',
      'The 3D model is the 2012 Dallara DW12 — the original version of today’s chassis.'
    ],
    wiki: 'https://en.wikipedia.org/wiki/IndyCar_Series',
    model: { uid: 'cd4cdf512b9741548924fa0cb943ed4b', name: '2012 Dallara DW12', author: 'Tiaan.Pretorius', license: 'CC Attribution',
             thumb: 'https://media.sketchfab.com/models/cd4cdf512b9741548924fa0cb943ed4b/thumbnails/76385ca48a844af1962ae658fafe2976/57c25aebc7b84ed4a92e5f2dab845666.jpeg' }
  },
  {
    id: 'nascar',
    name: 'NASCAR Cup',
    tag: 'Stock car',
    type: 'stock',
    desc: 'V8 thunder, three-wide pack racing, and bumper-to-bumper finishes at 200 mph.',
    summary: 'NASCAR’s Cup Series is America’s most popular motorsport. Since 2022 every team runs the “Next Gen” car — a shared chassis wearing Chevrolet, Ford or Toyota bodywork — racing mostly on ovals, plus a few road courses.',
    specs: [
      ['Engine', '5.86L (358 cu in) V8, no turbo'],
      ['Power', '~670–750 hp (less at Daytona & Talladega)'],
      ['Transmission', '5-speed sequential'],
      ['Top speed', '~200 mph in the draft'],
      ['Wheels', '18-inch, single center lug'],
      ['Car', 'Next Gen (2022–present)']
    ],
    facts: [
      'At superspeedways, cars race inches apart in giant drafting packs.',
      'A four-tire pit stop with fuel takes around 10 seconds.',
      'The Daytona 500 opens the season and is its biggest race.'
    ],
    wiki: 'https://en.wikipedia.org/wiki/Next_Gen_(NASCAR)',
    model: { uid: 'ee5c5a92b1c44fac8ed070f684108225', name: '2022 NASCAR Chevrolet Camaro ZL1 Next Gen', author: 'Ddiaz Design', license: 'CC Attribution',
             thumb: 'https://media.sketchfab.com/models/ee5c5a92b1c44fac8ed070f684108225/thumbnails/bef08b40bede432f948ed0e2b29ec5fd/6df1df3a25ff41e39812412e04a9d76a.jpeg' }
  },
  {
    id: 'topfuel',
    name: 'Top Fuel Dragster',
    tag: 'Drag racing',
    type: 'drag',
    desc: 'The quickest-accelerating machines on Earth: 1,000 feet in under 4 seconds.',
    summary: 'Top Fuel dragsters are NHRA’s fastest class — long, skinny rails with a supercharged, nitromethane-burning V8 behind the driver. Two cars race side by side down a 1,000-foot strip, and the whole run is over in less time than it takes to read this sentence.',
    specs: [
      ['Engine', '500 cu in supercharged Hemi V8'],
      ['Power', '~11,000 hp (estimated)'],
      ['Fuel', '~90% nitromethane'],
      ['1,000 ft time', '~3.6–3.7 s'],
      ['Top speed', '~335+ mph at the finish'],
      ['0–100 mph', '~0.8 s'],
      ['Length', '~25 ft wheelbase'],
      ['Engine life', 'Rebuilt after every run']
    ],
    facts: [
      'Drivers feel around 5 g at launch — more than astronauts on a Space Shuttle.',
      'They need parachutes to stop.',
      'Funny Cars use the same engine under a flip-up carbon body with a shorter wheelbase.'
    ],
    wiki: 'https://en.wikipedia.org/wiki/Top_Fuel',
    model: { uid: '41256345adb74f9ca1525f35d4f0c9e6', name: 'Top Fuel Dragster', author: 'rhinoart', license: 'CC Attribution',
             thumb: 'https://media.sketchfab.com/models/41256345adb74f9ca1525f35d4f0c9e6/thumbnails/732b686cbb294a45b2016e792eda821d/454b34eca9ec4e9f90620ece192e0517.jpeg' }
  },
  {
    id: 'rally',
    name: 'WRC Rally1',
    tag: 'Rally',
    type: 'offroad',
    desc: 'All-wheel-drive monsters flat-out on gravel, snow and tarmac forest roads.',
    summary: 'The World Rally Championship runs on closed public roads — gravel, snow, ice and tarmac — one car at a time against the clock. Rally1 cars look like small hatchbacks but are purpose-built with all-wheel drive, huge suspension travel and a protective space frame.',
    specs: [
      ['Engine', '1.6L turbo 4-cylinder'],
      ['Power', '~365 hp (hybrid boost dropped for 2025)'],
      ['Drivetrain', 'All-wheel drive'],
      ['Transmission', '5-speed sequential'],
      ['Weight', '~2,600 lb'],
      ['Surfaces', 'Gravel, tarmac, snow & ice']
    ],
    facts: [
      'A co-driver reads out “pace notes” so the driver knows what’s around every blind corner.',
      'Big jumps at Rally Finland send cars flying well over 100 feet.',
      'The 3D model is Toyota’s GR Yaris Rally1.'
    ],
    wiki: 'https://en.wikipedia.org/wiki/World_Rally_Championship',
    model: { uid: '99acc44d75bc47de9bd4b55da6a4c6f1', name: 'WRC Toyota GR Yaris Rally1', author: 'SoMeOnE_7', license: '',
             thumb: 'https://media.sketchfab.com/models/99acc44d75bc47de9bd4b55da6a4c6f1/thumbnails/5cd92eda968b4adf9e34adcb764be13a/12717294b6b548ac8e4a657f0337d1d2.jpeg' }
  },
  {
    id: 'hypercar',
    name: 'Le Mans Hypercar',
    tag: 'Endurance',
    type: 'endurance',
    desc: 'Mostly hybrid prototypes that race flat-out for 24 hours straight at Le Mans.',
    summary: 'Hypercars are the top class of the World Endurance Championship. Manufacturers like Ferrari, Toyota, Cadillac and BMW build prototypes (almost all hybrids), and a “Balance of Performance” system keeps them evenly matched. The biggest race is the 24 Hours of Le Mans.',
    specs: [
      ['Power', '~670 hp combined (capped by the rules)'],
      ['Engine', 'Varies — Ferrari 499P: 3.0L twin-turbo V6 hybrid'],
      ['Top speed', '~210 mph on the Mulsanne Straight'],
      ['Weight', '~2,270 lb (adjusted per car)'],
      ['Race length', '24 hours, ~3,200 miles'],
      ['Drivers', '3 per car, taking turns']
    ],
    facts: [
      'Two rule sets (LMH and LMDh) race together, evened out by Balance of Performance.',
      'Drivers swap every few hours and race through the night.',
      'Le Mans has been running since 1923.',
      'The 3D model is the Porsche 963, which raced in the Hypercar class at Le Mans from 2023 to 2025.'
    ],
    wiki: 'https://en.wikipedia.org/wiki/Le_Mans_Hypercar',
    model: { uid: '0b46fea1c4484a0c887bd0b9aac86f5d', name: '2023 Porsche 963 LMDh Racecar No.5', author: 'Ddiaz Design', license: 'CC Attribution-NonCommercial-ShareAlike',
             thumb: 'https://media.sketchfab.com/models/0b46fea1c4484a0c887bd0b9aac86f5d/thumbnails/6b83a332ef1b4ed6a4d58552d8e82d1e/d4c450fadaa346cf83a0f8b8a2af29f0.jpeg' }
  },
  {
    id: 'formulae',
    name: 'Formula E',
    tag: 'Open-wheel',
    type: 'open',
    desc: 'All-electric single-seaters racing on tight city street circuits.',
    summary: 'Formula E is the world championship for electric single-seaters. Most races run on temporary street circuits in cities like London, Tokyo and Monaco, and energy management matters as much as raw speed.',
    specs: [
      ['Power', 'Up to 350 kW (~470 hp)'],
      ['Motors', 'Rear drive + front motor for regen and AWD boost'],
      ['Top speed', '~200 mph'],
      ['0–60 mph', '~1.8 s (Gen3 Evo, AWD mode)'],
      ['Regen braking', 'Up to 600 kW'],
      ['Weight', '~1,850 lb with driver'],
      ['Tracks', 'Mostly city street circuits']
    ],
    facts: [
      'The 2023–26 Gen3 car has no rear brake discs — the motor does the slowing.',
      '“Attack Mode” gives extra power if a driver drives off the racing line to arm it.',
      'The 3D model shows the Gen2 EVO design, which was cancelled before it ever raced; today’s car looks different.'
    ],
    wiki: 'https://en.wikipedia.org/wiki/Formula_E',
    model: { uid: '63787978b9e341d0b559dc265cf48317', name: 'Formula E Gen2 EVO Car', author: 'Naudaff3D', license: 'Editorial',
             thumb: 'https://media.sketchfab.com/models/63787978b9e341d0b559dc265cf48317/thumbnails/7141137802ad410798303bbc3beb37cf/12fc9894cb2e48dcbd6f35f00f742e0d.jpeg' }
  },
  {
    id: 'drift',
    name: 'Formula Drift',
    tag: 'Drift',
    type: 'stock',
    desc: 'Judged on line, angle and style — 1,000 hp cars going sideways in pairs.',
    summary: 'Formula Drift is the top US drifting series. Instead of racing the clock, drivers are judged on their line, angle and style as they slide through a course, usually in tandem battles where one car chases another inches apart.',
    specs: [
      ['Power', '~1,000+ hp is common'],
      ['Drivetrain', 'Rear-wheel drive only'],
      ['Engines', 'Anything goes — V8s, 2JZ sixes, rotaries'],
      ['Judging', 'Line, angle and style — not lap time'],
      ['Entry speed', '~90+ mph, sideways'],
      ['Tires', 'Several sets burned per event']
    ],
    facts: [
      'Tandem battles run twice, with the lead and chase cars swapping.',
      'Judges reward the chase car for staying as close as possible.',
      'The 3D model is a Toyota GR86 Formula Drift car.'
    ],
    wiki: 'https://en.wikipedia.org/wiki/Formula_D',
    model: { uid: '70011653fded4b03b4ff0060dc03956d', name: 'Toyota GR86 SilkBlaze Formula Drift', author: 'blakebella', license: '',
             thumb: 'https://media.sketchfab.com/models/70011653fded4b03b4ff0060dc03956d/thumbnails/216b8720122243bea78bea83cc12b521/3db41c8088654e7ca89d64fe046cfe8d.jpeg' }
  },
  {
    id: 'monster',
    name: 'Monster Truck',
    tag: 'Off-road',
    type: 'offroad',
    desc: '12,000-pound trucks on 66-inch tires doing backflips in stadiums.',
    summary: 'Monster trucks like those in Monster Jam are custom-built for stadium shows: racing, freestyle jumps, wheelies and flips. Under the famous bodies is a tube chassis with a huge supercharged V8 and long-travel suspension.',
    specs: [
      ['Engine', '540 cu in supercharged methanol V8'],
      ['Power', '~1,500 hp'],
      ['Weight', '~12,000 lb'],
      ['Tires', '66 in tall, 43 in wide'],
      ['Height', '~12 ft'],
      ['Jumps', '~30 ft high, 125+ ft long'],
      ['Top speed', '~70 mph']
    ],
    facts: [
      'Trucks can do backflips — and even front flips.',
      'Each tire weighs around 645 pounds.',
      'Grave Digger, the truck in the 3D model, debuted in 1982.'
    ],
    wiki: 'https://en.wikipedia.org/wiki/Monster_truck',
    model: { uid: '83415314073c42b4905be1093a3043cc', name: 'Monster Truck Grave Digger', author: 'Guillermo Gutiérrez', license: 'CC Attribution',
             thumb: 'https://media.sketchfab.com/models/83415314073c42b4905be1093a3043cc/thumbnails/253b1aa51e33485184071d57728c90dc/4bfc0f6616b6421687ad235255d40b53.jpeg' }
  },
  {
    id: 'trophy',
    name: 'Trophy Truck',
    tag: 'Off-road',
    type: 'offroad',
    desc: 'Desert racers built to fly over the Baja 1000 at triple-digit speeds.',
    summary: 'Trophy Trucks are the fastest class in desert racing, built to survive races like the Baja 1000 across Mexico’s Baja Peninsula. Massive suspension travel lets them soak up rocks, whoops and jumps at speeds most cars never reach on pavement.',
    specs: [
      ['Engine', 'Big V8, often 850–1,000 hp'],
      ['Suspension travel', '~30+ in (rear)'],
      ['Top speed', '~130+ mph on open desert'],
      ['Weight', '~6,500 lb'],
      ['Signature race', 'Baja 1000'],
      ['Build cost', 'Often $1M+']
    ],
    facts: [
      'The Baja 1000 has been run since 1967.',
      'Teams chase their truck across the desert with spare parts and fuel.',
      'The 3D model is a Toyota Baja Trophy Truck.'
    ],
    wiki: 'https://en.wikipedia.org/wiki/Trophy_truck',
    model: { uid: 'bd99fe67bfb84ba2a931e872361a6fb4', name: 'Toyota Baja Trophy Truck', author: 'CG Customs', license: '',
             thumb: 'https://media.sketchfab.com/models/bd99fe67bfb84ba2a931e872361a6fb4/thumbnails/555ca80f19cd42f79f6846388188ab46/534c00775cca47d4b65f8fa6647a98e0.jpeg' }
  }
];
