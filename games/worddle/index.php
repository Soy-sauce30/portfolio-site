<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Worddle — Sawyer</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/style.css">
  <link rel="stylesheet" href="/games/game.css">
  <script>
    (function(){var t=localStorage.getItem('theme')||(window.matchMedia('(prefers-color-scheme:light)').matches?'light':'dark');document.documentElement.setAttribute('data-theme',t)})();
  </script>
  <style>
    :root {
      --wd-correct: #15803d;
      --wd-present: #a16207;
      --wd-absent: #3f3f46;
    }
    [data-theme="light"] {
      --wd-correct: #16a34a;
      --wd-present: #ca8a04;
      --wd-absent: #9ca3af;
    }

    .wd-board {
      display: grid;
      grid-template-rows: repeat(6, 1fr);
      gap: 5px;
    }

    .wd-row {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 5px;
    }

    .wd-tile {
      width: 58px;
      height: 58px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.9rem;
      font-weight: 700;
      text-transform: uppercase;
      border: 2px solid var(--border);
      border-radius: 6px;
      color: var(--text);
      user-select: none;
      transition: border-color 0.1s;
    }

    .wd-tile.filled {
      border-color: var(--text-muted);
      animation: wd-pop 0.1s ease-out;
    }

    @keyframes wd-pop {
      0%   { transform: scale(1); }
      50%  { transform: scale(1.08); }
      100% { transform: scale(1); }
    }

    .wd-tile.reveal { animation: wd-flip 0.55s ease forwards; }

    @keyframes wd-flip {
      0%   { transform: rotateX(0); }
      45%  { transform: rotateX(90deg); }
      55%  { transform: rotateX(90deg); }
      100% { transform: rotateX(0); }
    }

    .wd-tile.correct,
    .wd-tile.present,
    .wd-tile.absent { color: #fff; border-color: transparent; }

    .wd-tile.correct { background: var(--wd-correct); }
    .wd-tile.present { background: var(--wd-present); }
    .wd-tile.absent  { background: var(--wd-absent); }

    .wd-row.shake { animation: wd-shake 0.45s; }

    @keyframes wd-shake {
      0%, 100%     { transform: translateX(0); }
      20%, 60%     { transform: translateX(-6px); }
      40%, 80%     { transform: translateX(6px); }
    }

    .wd-row.win .wd-tile { animation: wd-bounce 0.6s ease; }

    @keyframes wd-bounce {
      0%, 100% { transform: translateY(0); }
      40%      { transform: translateY(-14px); }
      70%      { transform: translateY(-5px); }
    }

    .wd-keyboard {
      display: flex;
      flex-direction: column;
      gap: 6px;
      width: 100%;
      max-width: 480px;
    }

    .wd-krow {
      display: flex;
      justify-content: center;
      gap: 5px;
    }

    .wd-key {
      font-family: inherit;
      font-size: 0.85rem;
      font-weight: 700;
      text-transform: uppercase;
      color: var(--text);
      background: var(--accent-dim);
      border: 1px solid var(--border);
      border-radius: 5px;
      height: 48px;
      flex: 1;
      min-width: 0;
      cursor: pointer;
      user-select: none;
      transition: background 0.15s, color 0.15s;
    }

    .wd-key:hover { background: var(--accent-glow); }
    .wd-key.wide { flex: 1.6; font-size: 0.68rem; letter-spacing: 0.04em; }

    .wd-key.correct { background: var(--wd-correct); border-color: transparent; color: #fff; }
    .wd-key.present { background: var(--wd-present); border-color: transparent; color: #fff; }
    .wd-key.absent  { background: var(--wd-absent);  border-color: transparent; color: #fff; opacity: 0.6; }

    @media (max-width: 460px) {
      .wd-tile { width: 48px; height: 48px; font-size: 1.5rem; }
      .wd-key { height: 44px; font-size: 0.78rem; }
    }
  </style>
</head>
<body>

  <?php include '../../header.php'; ?>

  <div class="game-wrap">

    <div class="game-head">
      <a class="game-back" href="/games/">&larr; All games</a>
      <h1 class="game-title">Worddle</h1>
      <p class="game-blurb">Guess the five-letter word in six tries. A new puzzle every day.</p>
    </div>

    <div class="game-stage">

      <div class="game-bar">
        <div class="game-stat">
          <span class="game-stat-label">Puzzle</span>
          <span class="game-stat-value" id="wdPuzzle">#1</span>
        </div>
        <div class="game-stat">
          <span class="game-stat-label">Played</span>
          <span class="game-stat-value" id="wdPlayed">0</span>
        </div>
        <div class="game-stat">
          <span class="game-stat-label">Win %</span>
          <span class="game-stat-value" id="wdWinPct">0</span>
        </div>
        <div class="game-stat">
          <span class="game-stat-label">Streak</span>
          <span class="game-stat-value" id="wdStreak">0</span>
        </div>
      </div>

      <div class="game-msg" id="wdMsg"></div>

      <div class="wd-board" id="wdBoard"></div>

      <div class="wd-keyboard" id="wdKeyboard"></div>

      <div class="game-bar">
        <button class="game-btn ghost" id="wdPractice">Practice word</button>
        <button class="game-btn ghost" id="wdShare">Share result</button>
      </div>

    </div>

    <p class="game-help">
      Type a guess and press <kbd>Enter</kbd>. Green = right letter, right spot.
      Yellow = right letter, wrong spot. Grey = not in the word.
    </p>

  </div>

  <?php include '../../footer.php'; ?>

  <script src="/script.js"></script>
  <script src="/theme.js"></script>
  <script>
  (function () {
    'use strict';

    // Answer pool — one of these is the solution each day.
    var ANSWERS = ('about above abuse actor acute admit adopt adult after again agent agree ahead alarm album '
      + 'alert alike alive allow alone along alter among anger angle angry apart apple apply arena argue arise '
      + 'armed arrow aside asset avoid awake award aware badly baker basic beach began begin begun being below '
      + 'bench birth black blame blank blast blind block blood board boost booth bound brain brand brass brave '
      + 'bread break breed brief bring broad broke brown build built burst buyer cable carry catch cause chain '
      + 'chair chart chase cheap check chest chief child chose civil claim class clean clear click climb clock '
      + 'close cloud coach coast could count court cover crack craft crash cream crime cross crowd crown curve '
      + 'cycle daily dance dated dealt death debut delay depth doing doubt dozen draft drama drawn dream dress '
      + 'drill drink drive drove dying eager early earth eight elite empty enemy enjoy enter entry equal error '
      + 'event every exact exist extra faith false fault fiber field fifth fifty fight final first fixed flash '
      + 'fleet floor fluid focus force forth forty forum found frame frank fresh front fruit fully funny giant '
      + 'given glass globe going grace grade grand grant grass great green gross group grown guard guess guest '
      + 'guide happy harsh heart heavy hence horse hotel house human ideal image index inner input issue joint '
      + 'judge known label large laser later laugh layer learn lease least leave legal level light limit lives '
      + 'local logic loose lower lucky lunch lying magic major maker march match maybe mayor meant media metal '
      + 'might minor minus mixed model money month moral motor mount mouse mouth movie music never newly night '
      + 'noise north noted novel nurse occur ocean offer often order other ought paint panel paper party peace '
      + 'phase phone photo piece pilot pitch place plain plane plant plate point pound power press price pride '
      + 'prime print prior prize proof proud prove queen quick quiet quite radio raise range rapid ratio reach '
      + 'ready refer right rigid rival river roman rough round route royal rural scale scene scope score sense '
      + 'serve seven shall shape share sharp sheet shelf shell shift shirt shock shoot short shown sight since '
      + 'sixth sixty sized skill sleep slide small smart smile smoke solid solve sorry sound south space spare '
      + 'speak speed spend spent split spoke sport staff stage stake stand start state steam steel stick still '
      + 'stock stone stood store storm story strip stuck study stuff style sugar suite super sweet table taken '
      + 'taste teach teeth thank theft their theme there these thick thing think third those three threw throw '
      + 'tight times tired title today topic total touch tough tower track trade train treat trend trial tried '
      + 'truck truly trust truth twice under union unity until upper upset urban usage usual valid value video '
      + 'virus visit vital voice waste watch water wheel where which while white whole whose woman women world '
      + 'worry worse worst worth would wound write wrong wrote yield young youth').split(' ');

    // Additional words accepted as guesses but never chosen as the answer.
    var EXTRA = ('adieu aisle alien amber ample ankle apron aroma bacon badge bagel banjo barge basil batch '
      + 'beast belly bible bingo bison blaze bleak blend bless bliss bloom blues blunt boast bogus bonus brace '
      + 'braid bribe brick bride brink brisk broth brush buddy bulky bunch bunny burnt bushy cabin cadet cameo '
      + 'canal candy canoe cargo carve cedar chalk champ chant chaos charm cheek cheer chess chill chime chirp '
      + 'chord chore chunk churn cider cigar circa civic clamp clash clasp clerk cliff cling cloak clone clout '
      + 'clove clown clump cobra cocoa colon comet comic comma coral couch cough crank crate crave crawl crazy '
      + 'creek creep crepe crest crisp croak crumb crush crust cubic cumin curly curry curse cyber cynic daisy '
      + 'debit decal decay decoy defer deity delta dense depot derby deter devil diary dicey digit dimly diner '
      + 'dingy dirty ditch ditto diver dizzy dodge dogma donor donut dough downy drain drank dread drift drone '
      + 'drool droop dryer dully dummy dunes dusky dusty dwarf dwell eagle easel eaten eater ebony eerie egret '
      + 'eject elbow elder elect elope elude elves ember emcee emoji emote enact ended endow ensue envoy epoch '
      + 'epoxy equip erase erode erupt essay ether ethic ethos evade evict evoke exalt excel exert exile expel '
      + 'fable faced facet fairy fancy farce fatal fatty fauna favor feast feign felon femur fence feral ferry '
      + 'fetch fever fewer ficus fiend fiery filly filmy filth finch finer fired fishy fixer fizzy flair flake '
      + 'flaky flame flank flare flask fleck flesh flick flier fling flint flirt float flock flood floss flour '
      + 'flown fluke flung flunk flush flute foamy focal foggy folio folly foray forge forgo forte foyer frail '
      + 'franc fraud freak freed freer friar fried frill frisk frock frost froth frown froze fudge fugue fungi '
      + 'funky furry fussy fuzzy gaudy gauge gaunt gauze gavel gecko geese genie genre ghost ghoul giddy girth '
      + 'giver gizmo gland glare glaze gleam glean glide glint gloat gloom glory gloss glove gnome godly golem '
      + 'goner goofy goose gorge gouge gourd grail grain grape graph grasp grate grave gravy graze greed greet '
      + 'grief grill grime grimy grind gripe groan groin groom grope grout grove growl grunt guava guild guile '
      + 'guilt guise gulch gully gumbo gummy guppy gusto gusty gypsy habit hairy halve handy hardy hasty hatch '
      + 'hater haunt haven havoc hazel heady heave hedge hefty heist helix hello hippo hitch hoard hobby hoist '
      + 'holly homer honey honor horde hotly hound hovel hover howdy humid humor hunch hurry husky hutch hydro '
      + 'hyena hyper igloo imbue impel imply inbox incur inept inert infer ingot inlay inlet intro irate irony '
      + 'islet ivory jaunt jazzy jelly jerky jetty jewel jiffy joker jolly joust juice juicy jumbo jumpy juror '
      + 'karma kayak kebab khaki kinky kiosk kitty knack knave knead kneel knelt knife knock knoll koala krill '
      + 'labor laden ladle lager lance lanky lapel lapse larva lasso latch lathe latte leaky leash ledge leech '
      + 'leery lemon lemur lever lilac limbo linen lingo llama loath lobby lodge lofty loyal lucid lumen lumpy '
      + 'lunar lupus lyric macaw macho madam mafia magma maize mambo mango manor maple marsh mason matte mauve '
      + 'meaty medal melon mercy merge merit merry messy midst milky mimic mince miner mirth moist molar moldy '
      + 'mocha mossy motel motto mould mound mourn mover mucus muddy mulch mummy mural murky mushy musty muted '
      + 'nacho nadir naive nasal nasty naval nerdy nerve newer nicer niche niece nifty ninja ninth noble nomad '
      + 'noose notch novae nudge nurse nutty nylon oaken oasis oaths obese oddly ocher olive omega onion onset '
      + 'opera opine optic orbit organ otter ounce outdo outer ovary owing owner oxide ozone pagan pager palsy '
      + 'panda pansy pants papal parka parry pasta pasty patch patio payer peach pearl pecan pedal penny perch '
      + 'peril perky pesky petal petty pesto phony piano picky piety piggy pinch pinky pious pixel pizza plaid '
      + 'plank plaza pleat plied pluck plumb plume plump plush poesy polar polka poppy porch posse pouch prank '
      + 'prawn preen prism privy probe prone prong prose proxy prune psalm pudgy puffy pulpy pulse punch pupil '
      + 'puppy puree purge purse pushy putty quack quail quake qualm quart quash quasi queer quell query quest '
      + 'queue quill quilt quirk quota quoth rabbi rabid radar rally ramen ranch randy raspy raven rayon realm '
      + 'rebel rebus recap regal rehab reign relax relay relic remit renal renew repay repel reply resin retch '
      + 'retro rhino rhyme ricky ridge rifle rinse ripen risen risky rivet roast robin robot rocky rodeo rogue '
      + 'roomy roost rotor rouge rowdy ruddy ruler rumor runic runny rusty saber sadly safer saint salad salon '
      + 'salsa salty salve sandy sappy sassy satin sauce saucy sauna savor savvy scald scalp scant scarf scary '
      + 'scold scoop scoot scorn scour scout scrap screw scrub scuba sedan seedy segue seize sepia serum shack '
      + 'shade shady shaft shaky shale shame shank shawl shear sheen sheep sheer shine shiny shire shoal shore '
      + 'shorn shout shove shrew shrub shrug shuck shunt shush siege sieve silky silly silty since sinew singe '
      + 'siren sixty skate skiff skimp skirt skulk skull skunk slack slain slang slant slash slate sleek sleet '
      + 'slept slice slick slime slimy sling slink slope slosh sloth slump slung slurp slush slyly smack smash '
      + 'smear smelt smirk smite smock smote snack snail snake snaky snare snarl sneak sneer snide sniff snipe '
      + 'snoop snore snort snout snowy snuck soapy sober soggy solar sonic sooty sorta sound spade spank spark '
      + 'spasm spawn spear speck spice spicy spied spike spiky spill spilt spine spiny spire spite splat spoil '
      + 'spool spoon spore spout spray spree sprig spunk spurn spurt squad squat squib stack stain stair stalk '
      + 'stall stamp stand stank stark stash stave stead steed steep steer stein stern stiff sting stink stint '
      + 'stoic stoke stole stomp stony stool stoop stork stout stove strap straw stray strut stung stunt suave '
      + 'sugar sulky sully sumac sunny surge surly sushi swami swamp swarm swash swath swear sweat sweep swell '
      + 'swept swift swill swine swing swirl swish swoon swoop sword swore sworn synod syrup tabby taboo tacit '
      + 'tacky taffy taint taken talon tamer tango tangy taper tapir tardy tarot taunt tawny teary tease techy '
      + 'tempo tenet tenor tense tepee tepid terse testy theta thigh thong thorn those three thumb thump '
      + 'thyme tiara tibia tidal tiger tilde timid tipsy titan toast today toddy token tonal tonic tooth topaz '
      + 'torch torso torus totem toxic trace tract trail trait tramp trash trawl tread tribe trice trick tripe '
      + 'trite troll troop trout trove truce trump trunk truss tulip tumor tunic turbo tutor twang tweak tweed '
      + 'tweet twine twirl twist udder ulcer ultra umbra unfed unfit unify unite unlit unmet unwed usher utile '
      + 'utter vague valet valor valve vapid vapor vault vegan venom venue verge verse verso vicar vigil vigor '
      + 'villa vinyl viola viper viral visor vixen vocal vodka vogue vomit voter vouch vowel wacky wafer wager '
      + 'wagon waist waive waltz warty washy waxen weary weave wedge weedy weigh weird welsh whale wharf wheat '
      + 'whelp whiff whine whiny whirl whisk wider widow width wield wimpy wince winch windy wiper wired wispy '
      + 'witty woken woody wooer wooly wordy world worse wrath wreak wreck wrest wring wrist yacht yearn yeast '
      + 'yokel yolky young yucky yummy zebra zesty zonal').split(' ');

    var VALID = Object.create(null);
    ANSWERS.forEach(function (w) { VALID[w] = true; });
    EXTRA.forEach(function (w) { VALID[w] = true; });

    var ROWS = 6, COLS = 5;
    var EPOCH = Date.UTC(2026, 0, 1); // puzzle #1

    var boardEl    = document.getElementById('wdBoard');
    var keyboardEl = document.getElementById('wdKeyboard');
    var msgEl      = document.getElementById('wdMsg');

    var answer = '';
    var guesses = [];      // committed guesses
    var current = '';      // row being typed
    var over = false;
    var won = false;
    var daily = true;
    var dayNum = 0;
    var busy = false;      // true while reveal animation runs
    var msgTimer = null;

    /* ---------- puzzle selection ---------- */

    function todayIndex() {
      var now = new Date();
      var local = Date.UTC(now.getFullYear(), now.getMonth(), now.getDate());
      return Math.floor((local - EPOCH) / 86400000);
    }

    // Deterministic shuffle offset so consecutive days aren't alphabetically adjacent.
    function answerForDay(n) {
      var h = 2166136261;
      var s = 'worddle-' + n;
      for (var i = 0; i < s.length; i++) {
        h ^= s.charCodeAt(i);
        h = (h * 16777619) >>> 0;
      }
      return ANSWERS[h % ANSWERS.length];
    }

    /* ---------- storage ---------- */

    var STATE_KEY = 'worddle-state';
    var STATS_KEY = 'worddle-stats';

    function loadStats() {
      try {
        var s = JSON.parse(localStorage.getItem(STATS_KEY));
        if (s && typeof s.played === 'number') return s;
      } catch (e) {}
      return { played: 0, wins: 0, streak: 0, best: 0, lastDay: null };
    }

    function saveStats(s) {
      try { localStorage.setItem(STATS_KEY, JSON.stringify(s)); } catch (e) {}
    }

    function saveState() {
      if (!daily) return;
      try {
        localStorage.setItem(STATE_KEY, JSON.stringify({
          day: dayNum, guesses: guesses, over: over, won: won
        }));
      } catch (e) {}
    }

    function loadState() {
      try {
        var s = JSON.parse(localStorage.getItem(STATE_KEY));
        if (s && s.day === dayNum && Array.isArray(s.guesses)) return s;
      } catch (e) {}
      return null;
    }

    function renderStats() {
      var s = loadStats();
      document.getElementById('wdPlayed').textContent = s.played;
      document.getElementById('wdWinPct').textContent =
        s.played ? Math.round((s.wins / s.played) * 100) : 0;
      document.getElementById('wdStreak').textContent = s.streak;
    }

    /* ---------- board ---------- */

    function buildBoard() {
      boardEl.innerHTML = '';
      for (var r = 0; r < ROWS; r++) {
        var row = document.createElement('div');
        row.className = 'wd-row';
        row.dataset.row = r;
        for (var c = 0; c < COLS; c++) {
          var t = document.createElement('div');
          t.className = 'wd-tile';
          row.appendChild(t);
        }
        boardEl.appendChild(row);
      }
    }

    function rowEl(i) { return boardEl.children[i]; }

    // Standard two-pass scoring so duplicate letters resolve correctly.
    function score(guess) {
      var res = new Array(COLS);
      var pool = answer.split('');
      var i;
      for (i = 0; i < COLS; i++) {
        if (guess[i] === answer[i]) { res[i] = 'correct'; pool[i] = null; }
      }
      for (i = 0; i < COLS; i++) {
        if (res[i]) continue;
        var at = pool.indexOf(guess[i]);
        if (at !== -1) { res[i] = 'present'; pool[at] = null; }
        else { res[i] = 'absent'; }
      }
      return res;
    }

    function paintRow(i, guess, marks, animate) {
      var row = rowEl(i);
      for (var c = 0; c < COLS; c++) {
        (function (tile, ch, mark, delay) {
          tile.textContent = ch;
          if (animate) {
            tile.style.animationDelay = delay + 'ms';
            tile.classList.add('reveal');
            setTimeout(function () { tile.classList.add(mark); }, delay + 250);
          } else {
            tile.classList.add(mark);
          }
        })(row.children[c], guess[c], marks[c], c * 220);
      }
    }

    function renderCurrent() {
      var row = rowEl(guesses.length);
      if (!row) return;
      for (var c = 0; c < COLS; c++) {
        var tile = row.children[c];
        var ch = current[c] || '';
        if (tile.textContent !== ch) {
          tile.textContent = ch;
          tile.classList.toggle('filled', !!ch);
          if (ch) {
            tile.classList.remove('filled');
            void tile.offsetWidth; // restart the pop animation
            tile.classList.add('filled');
          }
        }
      }
    }

    /* ---------- keyboard ---------- */

    var KEY_ROWS = ['qwertyuiop', 'asdfghjkl', 'zxcvbnm'];
    var keyEls = {};

    function buildKeyboard() {
      keyboardEl.innerHTML = '';
      KEY_ROWS.forEach(function (letters, idx) {
        var row = document.createElement('div');
        row.className = 'wd-krow';
        if (idx === 2) row.appendChild(makeKey('Enter', 'enter', true));
        letters.split('').forEach(function (ch) {
          var k = makeKey(ch, ch, false);
          keyEls[ch] = k;
          row.appendChild(k);
        });
        if (idx === 2) row.appendChild(makeKey('Back', 'back', true));
        keyboardEl.appendChild(row);
      });
    }

    function makeKey(label, value, wide) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'wd-key' + (wide ? ' wide' : '');
      b.textContent = label;
      b.dataset.key = value;
      b.addEventListener('click', function () {
        press(value);
        b.blur();
      });
      return b;
    }

    var RANK = { absent: 0, present: 1, correct: 2 };

    function paintKeys(guess, marks) {
      for (var i = 0; i < COLS; i++) {
        var el = keyEls[guess[i]];
        if (!el) continue;
        var cur = el.dataset.state;
        if (cur && RANK[cur] >= RANK[marks[i]]) continue;
        el.classList.remove('correct', 'present', 'absent');
        el.classList.add(marks[i]);
        el.dataset.state = marks[i];
      }
    }

    function resetKeys() {
      Object.keys(keyEls).forEach(function (ch) {
        var el = keyEls[ch];
        el.classList.remove('correct', 'present', 'absent');
        delete el.dataset.state;
      });
    }

    /* ---------- messages ---------- */

    function say(text, sticky) {
      clearTimeout(msgTimer);
      msgEl.textContent = text;
      if (!sticky && text) {
        msgTimer = setTimeout(function () { msgEl.textContent = ''; }, 1800);
      }
    }

    function shake() {
      var row = rowEl(guesses.length);
      if (!row) return;
      row.classList.add('shake');
      setTimeout(function () { row.classList.remove('shake'); }, 450);
    }

    /* ---------- input ---------- */

    function press(key) {
      if (over || busy) return;
      if (key === 'enter') return submit();
      if (key === 'back') {
        current = current.slice(0, -1);
        renderCurrent();
        return;
      }
      if (/^[a-z]$/.test(key) && current.length < COLS) {
        current += key;
        renderCurrent();
      }
    }

    function submit() {
      if (current.length < COLS) { say('Not enough letters'); shake(); return; }
      if (!VALID[current]) { say('Not in word list'); shake(); return; }

      var guess = current;
      var marks = score(guess);
      var rowIndex = guesses.length;

      guesses.push(guess);
      current = '';
      busy = true;

      paintRow(rowIndex, guess, marks, true);

      setTimeout(function () {
        paintKeys(guess, marks);
        busy = false;

        if (guess === answer) {
          won = true;
          over = true;
          rowEl(rowIndex).classList.add('win');
          say(['Genius', 'Magnificent', 'Impressive', 'Splendid', 'Great', 'Phew'][rowIndex], true);
          finish();
        } else if (guesses.length === ROWS) {
          over = true;
          say('The word was ' + answer.toUpperCase(), true);
          finish();
        }
        saveState();
      }, (COLS - 1) * 220 + 400);

      saveState();
    }

    function finish() {
      if (!daily) return;
      var s = loadStats();
      if (s.lastDay === dayNum) return; // already counted
      s.played++;
      if (won) {
        s.wins++;
        s.streak = (s.lastDay === dayNum - 1) ? s.streak + 1 : 1;
        if (s.streak > s.best) s.best = s.streak;
      } else {
        s.streak = 0;
      }
      s.lastDay = dayNum;
      saveStats(s);
      renderStats();
    }

    document.addEventListener('keydown', function (e) {
      if (e.ctrlKey || e.metaKey || e.altKey) return;
      if (e.key === 'Enter') { e.preventDefault(); press('enter'); }
      else if (e.key === 'Backspace') { e.preventDefault(); press('back'); }
      else if (/^[a-zA-Z]$/.test(e.key)) press(e.key.toLowerCase());
    });

    /* ---------- share ---------- */

    document.getElementById('wdShare').addEventListener('click', function () {
      if (!over) { say('Finish the puzzle first'); return; }
      var lines = guesses.map(function (g) {
        return score(g).map(function (m) {
          return m === 'correct' ? '🟩' : m === 'present' ? '🟨' : '⬛';
        }).join('');
      });
      var header = 'Worddle ' + (daily ? '#' + (dayNum + 1) : 'practice') + ' '
        + (won ? guesses.length : 'X') + '/' + ROWS;
      var text = header + '\n\n' + lines.join('\n');

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(
          function () { say('Copied to clipboard'); },
          function () { say('Could not copy'); }
        );
      } else {
        say('Clipboard unavailable');
      }
    });

    /* ---------- practice / new game ---------- */

    document.getElementById('wdPractice').addEventListener('click', function () {
      daily = false;
      answer = ANSWERS[Math.floor(Math.random() * ANSWERS.length)];
      guesses = [];
      current = '';
      over = false;
      won = false;
      busy = false;
      buildBoard();
      resetKeys();
      say('Practice word — stats are not affected');
      document.getElementById('wdPuzzle').textContent = 'Practice';
    });

    /* ---------- boot ---------- */

    function init() {
      dayNum = todayIndex();
      answer = answerForDay(dayNum);
      document.getElementById('wdPuzzle').textContent = '#' + (dayNum + 1);

      buildBoard();
      buildKeyboard();
      renderStats();

      var saved = loadState();
      if (saved) {
        guesses = saved.guesses;
        over = !!saved.over;
        won = !!saved.won;
        guesses.forEach(function (g, i) {
          var marks = score(g);
          paintRow(i, g, marks, false);
          paintKeys(g, marks);
        });
        if (over) {
          say(won ? 'Solved in ' + guesses.length + ' — back tomorrow' : 'The word was ' + answer.toUpperCase(), true);
        }
      }
    }

    init();
  })();
  </script>
</body>
</html>
