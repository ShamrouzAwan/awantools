/* =====================================================================
   Developer Generator Toolkit v3 — Animated + Real-time Preview
   100% client-side. Platform CSS variables only.
   ===================================================================== */
var DGT = (function () {
    'use strict';

    /* ── Crypto random helpers ───────────────────────────────────── */
    function randBytes(n) { var a = new Uint8Array(n); crypto.getRandomValues(a); return a; }
    function randHex(n)   { return Array.from(randBytes(n)).map(b => ('0'+b.toString(16)).slice(-2)).join(''); }
    function randInt(lo, hi) { var b = new Uint32Array(1); crypto.getRandomValues(b); return lo + (b[0] % (hi - lo + 1)); }
    function randItem(a)  { return a[randInt(0, a.length - 1)]; }
    function shuffle(a)   { var r=a.slice(); for(var i=r.length-1;i>0;i--){var j=randInt(0,i);var t=r[i];r[i]=r[j];r[j]=t;} return r; }

    /* ── UUID ─────────────────────────────────────────────────────── */
    function uuidV4() {
        var b=randBytes(16); b[6]=(b[6]&0x0f)|0x40; b[8]=(b[8]&0x3f)|0x80;
        var h=Array.from(b).map(x=>('0'+x.toString(16)).slice(-2));
        return h[0]+h[1]+h[2]+h[3]+'-'+h[4]+h[5]+'-'+h[6]+h[7]+'-'+h[8]+h[9]+'-'+h[10]+h[11]+h[12]+h[13]+h[14]+h[15];
    }
    function uuidV1() {
        var ms=Date.now(), tL=ms&0xffffffff, tM=(ms/0x100000000|0)&0xffff, tH=((ms/0x1000000000000|0)&0x0fff)|0x1000;
        var cl=randBytes(2); cl[0]=(cl[0]&0x3f)|0x80; var nd=randHex(6);
        var f=(n,l)=>('0'.repeat(l)+n.toString(16)).slice(-l);
        return f(tL,8)+'-'+f(tM,4)+'-'+f(tH,4)+'-'+('0'+cl[0].toString(16)).slice(-2)+('0'+cl[1].toString(16)).slice(-2)+'-'+nd;
    }
    function uuidV7() {
        var ms=Date.now(),b=randBytes(16);
        b[0]=(ms/0x10000000000)&0xff; b[1]=(ms/0x100000000)&0xff; b[2]=(ms/0x1000000)&0xff;
        b[3]=(ms/0x10000)&0xff; b[4]=(ms/0x100)&0xff; b[5]=ms&0xff;
        b[6]=(b[6]&0x0f)|0x70; b[8]=(b[8]&0x3f)|0x80;
        var h=Array.from(b).map(x=>('0'+x.toString(16)).slice(-2));
        return h[0]+h[1]+h[2]+h[3]+'-'+h[4]+h[5]+'-'+h[6]+h[7]+'-'+h[8]+h[9]+'-'+h[10]+h[11]+h[12]+h[13]+h[14]+h[15];
    }
    var ULID_CHARS='0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    function ulid(){var ms=Date.now(),t='';for(var i=9;i>=0;i--){t=ULID_CHARS[ms%32]+t;ms=Math.floor(ms/32);}var r='';for(var j=0;j<16;j++)r+=ULID_CHARS[randInt(0,31)];return t+r;}
    var NID_CHARS='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-';
    function nanoid(n){n=n||21;return Array.from(randBytes(n)).map(x=>NID_CHARS[x%64]).join('');}
    function objectId(){return (Math.floor(Date.now()/1000).toString(16).padStart(8,'0'))+randHex(8)+randHex(6);}
    function snowflake(){var ep=1288834974657,ms=Date.now()-ep;return (BigInt(ms)*BigInt(4194304)+BigInt(randInt(0,31))*BigInt(4096)+BigInt(randInt(0,4095))).toString();}
    var UUID_RE=/^[0-9a-f]{8}-[0-9a-f]{4}-[1-7][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

    /* ── Password / Secrets ───────────────────────────────────────── */
    var CH={U:'ABCDEFGHIJKLMNOPQRSTUVWXYZ',L:'abcdefghijklmnopqrstuvwxyz',N:'0123456789',S:'!@#$%^&*()-_=+[]{}|;:,.<>?'};
    function password(len,upper,lower,nums,syms){
        var pool='',req=[];
        if(upper){pool+=CH.U;req.push(randItem(CH.U.split('')));}
        if(lower){pool+=CH.L;req.push(randItem(CH.L.split('')));}
        if(nums) {pool+=CH.N;req.push(randItem(CH.N.split('')));}
        if(syms) {pool+=CH.S;req.push(randItem(CH.S.split('')));}
        if(!pool)pool=CH.L+CH.N;
        var arr=req.slice(),p=pool.split('');
        while(arr.length<len)arr.push(randItem(p));
        return shuffle(arr).join('');
    }
    function pwStrength(pw){
        var s=0;
        if(pw.length>=8)s++;if(pw.length>=12)s++;if(pw.length>=16)s++;
        if(/[A-Z]/.test(pw))s++;if(/[a-z]/.test(pw))s++;
        if(/[0-9]/.test(pw))s++;if(/[^A-Za-z0-9]/.test(pw))s++;
        return Math.min(4,Math.floor(s/1.5));
    }
    var WORDS=['apple','brave','cloud','delta','eagle','flame','grace','honey','jungle','karma','lemon','magic','noble','ocean','prime','quest','river','storm','tiger','ultra','vivid','water','yacht','zappy','amber','burst','crisp','dance','ember','fresh','glint','haven','indie','jolly','knack','lunar','mirth','night','olive','pixel','quirk','radio','sunny','union','vista','woven','young'];
    function passphrase(n,sep){var w=[];for(var i=0;i<n;i++)w.push(randItem(WORDS));return w.join(sep);}

    /* ── Fake Data ────────────────────────────────────────────────── */
    var FM=['James','John','Robert','Michael','William','David','Richard','Joseph','Thomas','Charles','Daniel','Paul','Mark','Steven','Edward'],
        FF=['Mary','Patricia','Jennifer','Linda','Barbara','Elizabeth','Susan','Jessica','Sarah','Karen','Lisa','Nancy','Betty','Sandra','Ashley'],
        FL=['Smith','Johnson','Williams','Brown','Jones','Garcia','Miller','Davis','Rodriguez','Martinez','Hernandez','Lopez','Wilson','Anderson','Thomas'],
        FD=['gmail.com','yahoo.com','hotmail.com','outlook.com','protonmail.com','icloud.com'],
        FJ=['Software Engineer','Data Analyst','Product Manager','Designer','DevOps Engineer','QA Engineer','Backend Developer','Frontend Developer','Full Stack Developer','Database Administrator'],
        FC=['Acme Corp','Globex Inc','Initech LLC','Aperture Science','Wayne Enterprises','Stark Industries','Pied Piper','Hooli Corp','Dunder Mifflin','Prestige Worldwide'],
        FCT=['New York','Los Angeles','Chicago','Houston','Phoenix','Philadelphia','San Jose','Austin','Dallas','San Francisco'],
        FCO=['United States','United Kingdom','Canada','Australia','Germany','France','Japan','Brazil','India'],
        FST=['Main St','Oak Ave','Maple Dr','Cedar Ln','Pine Rd','Elm St','Park Ave','Lake Dr','River Rd'];

    function fakePerson(){
        var g=randInt(0,1),fn=g?randItem(FM):randItem(FF),ln=randItem(FL),yr=randInt(1965,2002),age=2026-yr;
        var email=(fn+'.'+ln).toLowerCase().replace(/\s/,'')+randInt(10,99)+'@'+randItem(FD);
        return{name:fn+' '+ln,first:fn,last:ln,gender:g?'Male':'Female',age,dob:yr+'-'+String(randInt(1,12)).padStart(2,'0')+'-'+String(randInt(1,28)).padStart(2,'0'),email,phone:'+1-'+randInt(200,999)+'-'+randInt(100,999)+'-'+randInt(1000,9999),job:randItem(FJ),company:randItem(FC),street:randInt(10,999)+' '+randItem(FST),city:randItem(FCT),country:randItem(FCO),zip:randInt(10000,99999)};
    }
    function randomDate(from,to){from=from||new Date(2020,0,1).getTime();to=to||Date.now();return new Date(from+Math.random()*(to-from)).toISOString().slice(0,19).replace('T',' ');}

    /* ── Lorem ────────────────────────────────────────────────────── */
    var LOREM={
        classic:['lorem','ipsum','dolor','sit','amet','consectetur','adipiscing','elit','sed','do','eiusmod','tempor','incididunt','ut','labore','et','dolore','magna','aliqua','enim','ad','minim','veniam','quis','nostrud','exercitation','ullamco','laboris','nisi','aliquip','ex','ea','commodo','consequat','duis','aute','irure','reprehenderit','voluptate','velit','esse','cillum','eu','fugiat','nulla','pariatur','excepteur','sint','occaecat','cupidatat','non','proident','sunt','culpa','officia','deserunt','mollit','anim','id','est','laborum'],
        bacon:   ['bacon','ipsum','dolor','amet','pancetta','meatball','short','loin','corned','beef','tenderloin','turkey','pork','belly','chuck','ribeye','fatback','ham','hock','jerky','andouille','pastrami','sirloin','capicola','tri-tip','brisket','prosciutto','salami','sausage','spare','ribs','ground','round','flank','shankle','frankfurter'],
        hipster: ['artisan','retro','twee','normcore','flexitarian','sriracha','keffiyeh','synth','gastropub','dreamcatcher','tofu','plaid','taxidermy','small','batch','craft','beer','sustainable','ethical','single-origin','coffee','gluten-free','quinoa','bicycle','kombucha','vinyl','forage','gentrify','shoreditch','williamsburg','fixie','tumblr','aesthetic','organic','meggings'],
        dev:     ['algorithm','function','variable','boolean','integer','string','array','object','class','method','interface','module','component','library','framework','deployment','container','docker','kubernetes','microservice','api','endpoint','middleware','authentication','authorization','database','query','schema','migration','test','pipeline','branch','commit','merge','refactor','debug','async','await','promise','callback','closure','prototype','abstraction'],
        corp:    ['synergy','leverage','disruptive','paradigm','scalable','agile','ideate','bandwidth','circle','back','deep','dive','low-hanging','fruit','move','needle','pivot','roadmap','takeaway','whiteboard','deliverable','ecosystem','end-to-end','traction','incentivize','iterate','proactive'],
        pirate:  ['arrr','ahoy','matey','shiver','timbers','landlubber','treasure','plunder','swashbuckler','corsair','jolly','roger','cutlass','cannonball','starboard','booty','doubloon','parrot','walk','plank','scurvy','privateers','raid','pillage','flagship'],
    };
    function loremGen(type,paras){
        var w=LOREM[type]||LOREM.classic,out=[];
        for(var p=0;p<paras;p++){var ss=[],sc=randInt(3,6);for(var s=0;s<sc;s++){var wc=randInt(8,18),wr=[];for(var i=0;i<wc;i++)wr.push(randItem(w));var sent=wr.join(' ');ss.push(sent.charAt(0).toUpperCase()+sent.slice(1)+'.');}out.push(ss.join(' '));}
        return out.join('\n\n');
    }

    /* ── Companies / Internet ─────────────────────────────────────── */
    var CW1=['Global','Alpha','Prime','Apex','Stellar','Nexus','Quantum','Vortex','Pinnacle','Summit','Horizon','Zenith','Atlas','Titan','Orion'],
        CW2=['Tech','Systems','Solutions','Dynamics','Ventures','Labs','Group','Industries','Networks','Digital','Software','Analytics','Consulting'],
        CT=['Inc.','LLC','Ltd.','Corp.'],
        SLOGANS=['Innovate. Iterate. Inspire.','Building tomorrow, today.','Your success is our mission.','Think different. Act bold.','Empowering people through technology.','Solutions that scale.'],
        TLDS=['com','io','co','net','org','dev','app','ai'];
    function fakeCompany(){var nm=randItem(CW1)+' '+randItem(CW2)+' '+randItem(CT),slug=nm.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').replace(/-?(inc|llc|ltd|corp)\b\.?/g,'').replace(/-+$/,''),dom=slug+'.'+randItem(TLDS);return{name:nm,domain:dom,email:'info@'+dom,slogan:randItem(SLOGANS),tax_id:randInt(10,99)+'-'+randInt(1000000,9999999)};}
    var UA=['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0.0.0 Safari/537.36','Mozilla/5.0 (Macintosh; Intel Mac OS X 14_4) AppleWebKit/605.1.15 Version/17.4 Safari/605.1.15','Mozilla/5.0 (X11; Linux x86_64; rv:125.0) Gecko/20100101 Firefox/125.0','Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 Version/17.4 Mobile Safari/604.1'];

    /* ── Color ────────────────────────────────────────────────────── */
    function hslToHex(h,s,l){s/=100;l/=100;var c=(1-Math.abs(2*l-1))*s,x=c*(1-Math.abs((h/60)%2-1)),m=l-c/2,r=0,g=0,b=0;if(h<60){r=c;g=x;}else if(h<120){r=x;g=c;}else if(h<180){g=c;b=x;}else if(h<240){g=x;b=c;}else if(h<300){r=x;b=c;}else{r=c;b=x;}var th=n=>('0'+Math.round((n+m)*255).toString(16)).slice(-2);return '#'+th(r)+th(g)+th(b);}
    function hexToRgb(h){return{r:parseInt(h.slice(1,3),16),g:parseInt(h.slice(3,5),16),b:parseInt(h.slice(5,7),16)};}
    function rgbToHsl(r,g,b){r/=255;g/=255;b/=255;var max=Math.max(r,g,b),min=Math.min(r,g,b),h,s,l=(max+min)/2;if(max===min){h=s=0;}else{var d=max-min;s=l>0.5?d/(2-max-min):d/(max+min);switch(max){case r:h=(g-b)/d+(g<b?6:0);break;case g:h=(b-r)/d+2;break;default:h=(r-g)/d+4;}h/=6;}return{h:Math.round(h*360),s:Math.round(s*100),l:Math.round(l*100)};}
    function colorPalette(base){var rgb=hexToRgb(base),hsl=rgbToHsl(rgb.r,rgb.g,rgb.b);return[90,75,55,35,15].map(l=>hslToHex(hsl.h,Math.max(20,hsl.s),l));}
    function hexToRgbStr(h){var rgb=hexToRgb(h);return 'rgb('+rgb.r+', '+rgb.g+', '+rgb.b+')';}

    /* ── XML escape ───────────────────────────────────────────────── */
    function xe(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

    /* ── Slug ─────────────────────────────────────────────────────── */
    var TLIT={'á':'a','à':'a','ä':'a','é':'e','è':'e','ê':'e','í':'i','ó':'o','ö':'o','ú':'u','ü':'u','ñ':'n','ç':'c'};
    function slugify(s,max){s=s.split('').map(c=>TLIT[c]||c).join('').toLowerCase().replace(/[^a-z0-9\s-]/g,'').replace(/[\s-]+/g,'-').replace(/^-+|-+$/g,'');if(max&&s.length>max)s=s.slice(0,max).replace(/-+$/,'');return s;}

    /* ── SQL helpers ──────────────────────────────────────────────── */
    function sqlRows(table,rows){
        var records=[];
        for(var i=0;i<rows;i++){var p=fakePerson();
            if(table==='users') records.push({id:i+1,name:p.name,email:p.email,username:p.first.toLowerCase()+randInt(10,99),created_at:randomDate()});
            else if(table==='products') records.push({id:i+1,name:'Product '+randHex(3).toUpperCase(),sku:'SKU-'+randHex(4).toUpperCase(),price:+(randInt(100,9999)/100).toFixed(2),stock:randInt(0,500),category:randItem(['Electronics','Clothing','Books','Food','Sports'])});
            else if(table==='orders') records.push({id:i+1,user_id:randInt(1,100),total:+(randInt(500,50000)/100).toFixed(2),status:randItem(['pending','processing','shipped','delivered']),created_at:randomDate()});
            else records.push({id:i+1,name:p.name,email:p.email,department:randItem(['Engineering','Marketing','Sales','HR','Finance']),salary:randInt(40000,150000),hired_at:randomDate()});
        }
        return records;
    }

    /* ── TOOL REGISTRY ────────────────────────────────────────────── */
    var TOOLS = [
        /* UUID & IDs */
        { id:'uuid-v4', cat:'uuid', title:'UUID v4', desc:'RFC 4122 cryptographically random UUID', tags:['secure','fast','popular'], related:['uuid-v7','uuid-v1','nanoid','ulid'], preview:true,
          inputs:[{type:'number',id:'qty',label:'Quantity',value:1,min:1,max:1000},{type:'toggle',id:'fmt',label:'Format',options:['lowercase','UPPERCASE'],value:'lowercase'},{type:'checkbox',id:'hyphens',label:'Include hyphens',checked:true}],
          fn(cfg){var n=+cfg.qty,up=cfg.fmt==='UPPERCASE',hy=cfg.hyphens;var out=[];for(var i=0;i<n;i++){var u=uuidV4();if(!hy)u=u.replace(/-/g,'');if(up)u=u.toUpperCase();out.push(u);}return out.join('\n');}
        },
        { id:'uuid-v7', cat:'uuid', title:'UUID v7', desc:'Timestamp-prefixed sortable UUID (draft RFC)', tags:['fast','popular'], related:['uuid-v4','ulid','nanoid'], preview:true,
          inputs:[{type:'number',id:'qty',label:'Quantity',value:5,min:1,max:1000}],
          fn(cfg){var out=[];for(var i=0;i<+cfg.qty;i++)out.push(uuidV7());return out.join('\n');}
        },
        { id:'uuid-v1', cat:'uuid', title:'UUID v1', desc:'Time-based UUID with random node', tags:['fast'], related:['uuid-v4','uuid-v7'],
          inputs:[{type:'number',id:'qty',label:'Quantity',value:5,min:1,max:100}],
          fn(cfg){var out=[];for(var i=0;i<+cfg.qty;i++)out.push(uuidV1());return out.join('\n');}
        },
        { id:'uuid-validate', cat:'uuid', title:'UUID Validator', desc:'Check if a string is a valid UUID (v1–v7)', tags:['fast'], related:['uuid-v4','uuid-v7'], preview:true,
          inputs:[{type:'text',id:'input',label:'UUID to validate',placeholder:'Paste UUID here…'}],
          fn(cfg){var v=cfg.input.trim();if(!v)return'Enter a UUID above.';var ok=UUID_RE.test(v);return ok?'✓ Valid UUID v'+v[14]+'\n\nInput: '+v:'✗ Invalid UUID\n\nInput: '+v;}
        },
        { id:'ulid', cat:'uuid', title:'ULID', desc:'Universally Unique Lexicographically Sortable Identifier', tags:['fast','popular'], related:['uuid-v7','nanoid'], preview:true,
          inputs:[{type:'number',id:'qty',label:'Quantity',value:5,min:1,max:1000}],
          fn(cfg){var out=[];for(var i=0;i<+cfg.qty;i++)out.push(ulid());return out.join('\n');}
        },
        { id:'nanoid', cat:'uuid', title:'NanoID', desc:'Compact URL-safe unique ID (A-Z a-z 0-9 _ -)', tags:['fast','popular'], related:['ulid','uuid-v4'], preview:true,
          inputs:[{type:'slider',id:'size',label:'Size',value:21,min:6,max:64,unit:'chars'},{type:'number',id:'qty',label:'Quantity',value:5,min:1,max:1000}],
          fn(cfg){var out=[];for(var i=0;i<+cfg.qty;i++)out.push(nanoid(+cfg.size));return out.join('\n');}
        },
        { id:'objectid', cat:'uuid', title:'MongoDB ObjectId', desc:'24-char hex identifier (4-byte ts + 8-byte random)', tags:['fast'], related:['uuid-v4','nanoid'],
          inputs:[{type:'number',id:'qty',label:'Quantity',value:5,min:1,max:200}],
          fn(cfg){var out=[];for(var i=0;i<+cfg.qty;i++)out.push(objectId());return out.join('\n');}
        },
        { id:'snowflake', cat:'uuid', title:'Snowflake ID', desc:'Twitter-style 64-bit timestamp + worker + sequence ID', tags:['fast'], related:['ulid','uuid-v7'],
          inputs:[{type:'number',id:'qty',label:'Quantity',value:5,min:1,max:100}],
          fn(cfg){var out=[];for(var i=0;i<+cfg.qty;i++)out.push(snowflake());return out.join('\n');}
        },
        { id:'session-id', cat:'uuid', title:'Session / Trace ID', desc:'Secure random ID for sessions, traces, and correlation', tags:['secure','fast'], related:['nanoid','uuid-v4'],
          inputs:[{type:'toggle',id:'fmt',label:'Format',options:['hex','base64url'],value:'hex'},{type:'slider',id:'len',label:'Length',value:32,min:8,max:128,unit:'chars'}],
          fn(cfg){var len=+cfg.len;if(cfg.fmt==='base64url'){var b=randBytes(Math.ceil(len*0.75));return btoa(String.fromCharCode(...b)).slice(0,len).replace(/[+/=]/g,c=>c==='+'?'-':c==='/'?'_':'');}return randHex(Math.ceil(len/2)).slice(0,len);}
        },
        /* Passwords */
        { id:'password', cat:'passwords', title:'Password Generator', desc:'Cryptographically secure password with configurable character sets', tags:['secure','popular'], related:['passphrase','api-key','jwt-secret'], preview:true,
          inputs:[{type:'slider',id:'len',label:'Length',value:16,min:4,max:128,unit:'chars'},{type:'checkbox',id:'upper',label:'Uppercase (A–Z)',checked:true},{type:'checkbox',id:'lower',label:'Lowercase (a–z)',checked:true},{type:'checkbox',id:'nums',label:'Numbers (0–9)',checked:true},{type:'checkbox',id:'syms',label:'Symbols (!@#…)',checked:false},{type:'number',id:'qty',label:'Quantity',value:5,min:1,max:50}],
          fn(cfg){var out=[];for(var i=0;i<+cfg.qty;i++)out.push(password(+cfg.len,cfg.upper,cfg.lower,cfg.nums,cfg.syms));return out.join('\n');}
        },
        { id:'passphrase', cat:'passwords', title:'Passphrase Generator', desc:'Word-based passphrase — memorable yet cryptographically strong', tags:['secure'], related:['password','pin'],
          inputs:[{type:'slider',id:'words',label:'Words',value:4,min:2,max:10,unit:'words'},{type:'select',id:'sep',label:'Separator',options:[{v:'-',l:'Hyphen (-)'},{v:' ',l:'Space'},{v:'.',l:'Dot (.)'},{v:'_',l:'Underscore'}],value:'-'},{type:'number',id:'qty',label:'Quantity',value:5,min:1,max:50}],
          fn(cfg){var out=[];for(var i=0;i<+cfg.qty;i++)out.push(passphrase(+cfg.words,cfg.sep));return out.join('\n');}
        },
        { id:'pin', cat:'passwords', title:'PIN Generator', desc:'Numeric PIN for ATM, 2FA, device lock, or any numeric code', tags:['fast'], related:['password','passphrase'],
          inputs:[{type:'toggle',id:'len',label:'Length',options:['4','6','8','10','12'],value:'6'},{type:'number',id:'qty',label:'Quantity',value:5,min:1,max:50}],
          fn(cfg){var out=[];for(var i=0;i<+cfg.qty;i++){var p='';for(var j=0;j<+cfg.len;j++)p+=randInt(0,9);out.push(p);}return out.join('\n');}
        },
        { id:'api-key', cat:'passwords', title:'API Key Generator', desc:'Secure hex API key with optional prefix', tags:['secure','popular'], related:['jwt-secret','csrf-token'],
          inputs:[{type:'text',id:'prefix',label:'Prefix (optional)',placeholder:'sk, pk, api…',value:''},{type:'toggle',id:'len',label:'Length',options:['16','24','32','48','64'],value:'32'},{type:'toggle',id:'case',label:'Case',options:['UPPER','lower'],value:'UPPER'},{type:'number',id:'qty',label:'Quantity',value:5,min:1,max:50}],
          fn(cfg){var out=[];for(var i=0;i<+cfg.qty;i++){var k=randHex(+cfg.len/2)[cfg.case==='UPPER'?'toUpperCase':'toLowerCase']();out.push(cfg.prefix?cfg.prefix+'_'+k:k);}return out.join('\n');}
        },
        { id:'jwt-secret', cat:'passwords', title:'JWT Secret', desc:'Cryptographically secure secret key for signing JSON Web Tokens', tags:['secure'], related:['api-key','csrf-token'],
          inputs:[{type:'toggle',id:'bits',label:'Bits',options:['128','256','384','512'],value:'256'},{type:'toggle',id:'fmt',label:'Format',options:['hex','base64'],value:'hex'}],
          fn(cfg){var hex=randHex(+cfg.bits/8);if(cfg.fmt==='base64'){var b=new Uint8Array(+cfg.bits/8);for(var i=0;i<b.length;i++)b[i]=parseInt(hex.substr(i*2,2),16);return btoa(String.fromCharCode(...b));}return hex;}
        },
        { id:'csrf-token', cat:'passwords', title:'CSRF Token', desc:'Random cryptographic token for CSRF protection', tags:['secure','fast'], related:['jwt-secret','api-key'],
          inputs:[{type:'toggle',id:'len',label:'Length',options:['16','32','48','64'],value:'32'},{type:'number',id:'qty',label:'Quantity',value:3,min:1,max:20}],
          fn(cfg){var out=[];for(var i=0;i<+cfg.qty;i++)out.push(randHex(+cfg.len/2));return out.join('\n');}
        },
        { id:'random-salt', cat:'passwords', title:'Random Salt', desc:'Cryptographic salt for password hashing or key derivation', tags:['secure'], related:['jwt-secret','api-key'],
          inputs:[{type:'toggle',id:'bits',label:'Bits',options:['64','128','256','512'],value:'128'},{type:'toggle',id:'fmt',label:'Format',options:['hex','base64'],value:'hex'}],
          fn(cfg){var hex=randHex(+cfg.bits/8);return cfg.fmt==='hex'?hex:btoa(hex);}
        },
        /* Random Data */
        { id:'random-number', cat:'random', title:'Random Number', desc:'Random integer or float within a configurable range', tags:['fast','popular'], related:['random-string','random-hex'],
          inputs:[{type:'number',id:'min',label:'Min',value:0},{type:'number',id:'max',label:'Max',value:100},{type:'toggle',id:'type',label:'Type',options:['integer','float'],value:'integer'},{type:'slider',id:'dec',label:'Decimals',value:2,min:0,max:10,unit:''},{type:'number',id:'qty',label:'Quantity',value:10,min:1,max:1000}],
          fn(cfg){var out=[];for(var i=0;i<+cfg.qty;i++){var min=parseFloat(cfg.min),max=parseFloat(cfg.max);out.push(cfg.type==='float'?(Math.random()*(max-min)+min).toFixed(+cfg.dec):randInt(Math.ceil(min),Math.floor(max)));}return out.join('\n');}
        },
        { id:'random-string', cat:'random', title:'Random String', desc:'Random string with configurable length and character set', tags:['fast','popular'], related:['random-hex','random-base64'],
          inputs:[{type:'slider',id:'len',label:'Length',value:32,min:1,max:512,unit:'chars'},{type:'select',id:'charset',label:'Character Set',options:[{v:'alphanum',l:'Alphanumeric'},{v:'alpha',l:'Alpha only'},{v:'upper',l:'UPPERCASE'},{v:'lower',l:'lowercase'},{v:'hex',l:'Hex'},{v:'num',l:'Numeric only'},{v:'sym',l:'Symbols'}],value:'alphanum'},{type:'number',id:'qty',label:'Quantity',value:5,min:1,max:50}],
          fn(cfg){var pool={alphanum:CH.U+CH.L+CH.N,alpha:CH.U+CH.L,upper:CH.U,lower:CH.L,hex:'0123456789abcdef',num:CH.N,sym:CH.S}[cfg.charset]||CH.U+CH.L+CH.N;var out=[];for(var i=0;i<+cfg.qty;i++){var s='';for(var j=0;j<+cfg.len;j++)s+=randItem(pool.split(''));out.push(s);}return out.join('\n');}
        },
        { id:'random-hex', cat:'random', title:'Hex String', desc:'Cryptographically random hexadecimal string', tags:['fast'], related:['random-string','random-base64'],
          inputs:[{type:'slider',id:'len',label:'Hex chars',value:32,min:2,max:256,unit:'chars'},{type:'toggle',id:'case',label:'Case',options:['lowercase','UPPERCASE'],value:'lowercase'},{type:'number',id:'qty',label:'Quantity',value:5,min:1,max:50}],
          fn(cfg){var out=[];for(var i=0;i<+cfg.qty;i++){var h=randHex(Math.ceil(+cfg.len/2)).slice(0,+cfg.len);out.push(cfg.case==='UPPERCASE'?h.toUpperCase():h);}return out.join('\n');}
        },
        { id:'random-base64', cat:'random', title:'Base64 String', desc:'Random base64-encoded string', tags:['fast'], related:['random-hex','random-string'],
          inputs:[{type:'slider',id:'len',label:'Length',value:32,min:4,max:256,unit:'chars'},{type:'number',id:'qty',label:'Quantity',value:5,min:1,max:50}],
          fn(cfg){var out=[];for(var i=0;i<+cfg.qty;i++){var b=randBytes(Math.ceil(+cfg.len*0.75));out.push(btoa(String.fromCharCode(...b)).slice(0,+cfg.len));}return out.join('\n');}
        },
        { id:'random-words', cat:'random', title:'Random Words', desc:'Pick random common English words', tags:['fast'], related:['random-sentence'],
          inputs:[{type:'slider',id:'count',label:'Words',value:10,min:1,max:200,unit:'words'},{type:'toggle',id:'sep',label:'Separator',options:['space','newline','comma'],value:'space'}],
          fn(cfg){var sep={space:' ',newline:'\n',comma:', '}[cfg.sep]||' ';var out=[];for(var i=0;i<+cfg.count;i++)out.push(randItem(WORDS));return out.join(sep);}
        },
        /* Lorem */
        { id:'lorem-classic', cat:'lorem', title:'Lorem Ipsum', desc:'Classic Latin placeholder text used since the 1500s', tags:['popular'], related:['lorem-dev','lorem-corp'], preview:true,
          inputs:[{type:'slider',id:'paras',label:'Paragraphs',value:3,min:1,max:20,unit:''},{type:'toggle',id:'fmt',label:'Format',options:['plain','html'],value:'plain'}],
          fn(cfg){var t=loremGen('classic',+cfg.paras);return cfg.fmt==='html'?t.split('\n\n').map(p=>'<p>'+p+'</p>').join('\n'):t;}
        },
        { id:'lorem-dev', cat:'lorem', title:'Developer Ipsum', desc:'Tech-themed placeholder using real developer terminology', tags:['popular'], related:['lorem-classic','lorem-corp'], preview:true,
          inputs:[{type:'slider',id:'paras',label:'Paragraphs',value:3,min:1,max:20,unit:''}],
          fn(cfg){return loremGen('dev',+cfg.paras);}
        },
        { id:'lorem-corp', cat:'lorem', title:'Corporate Ipsum', desc:'Business buzzword salad — synergy and paradigm shifts', tags:[], related:['lorem-classic','lorem-dev'], preview:true,
          inputs:[{type:'slider',id:'paras',label:'Paragraphs',value:3,min:1,max:20,unit:''}],
          fn(cfg){return loremGen('corp',+cfg.paras);}
        },
        { id:'lorem-bacon', cat:'lorem', title:'Bacon Ipsum', desc:'Meat-themed placeholder text for hungry developers', tags:[], related:['lorem-classic'], preview:true,
          inputs:[{type:'slider',id:'paras',label:'Paragraphs',value:3,min:1,max:20,unit:''}],
          fn(cfg){return loremGen('bacon',+cfg.paras);}
        },
        { id:'lorem-hipster', cat:'lorem', title:'Hipster Ipsum', desc:'Artisan, small-batch, single-origin placeholder text', tags:[], related:['lorem-classic'], preview:true,
          inputs:[{type:'slider',id:'paras',label:'Paragraphs',value:3,min:1,max:20,unit:''}],
          fn(cfg){return loremGen('hipster',+cfg.paras);}
        },
        { id:'lorem-pirate', cat:'lorem', title:'Pirate Ipsum', desc:'Arr matey! Swashbuckling placeholder text', tags:[], related:['lorem-bacon'], preview:true,
          inputs:[{type:'slider',id:'paras',label:'Paragraphs',value:3,min:1,max:20,unit:''}],
          fn(cfg){return loremGen('pirate',+cfg.paras);}
        },
        /* Fake Data */
        { id:'fake-person', cat:'fakedata', title:'Fake Person', desc:'Realistic fake identity with name, email, phone, DOB, and address', tags:['popular','bulk'], related:['fake-company','fake-ip'],
          inputs:[{type:'slider',id:'qty',label:'Records',value:5,min:1,max:100,unit:''},{type:'toggle',id:'fmt',label:'Output',options:['json','csv','text'],value:'json'}],
          fn(cfg){var n=+cfg.qty,pp=[];for(var i=0;i<n;i++)pp.push(fakePerson());if(cfg.fmt==='json')return n===1?JSON.stringify(pp[0],null,2):JSON.stringify(pp,null,2);if(cfg.fmt==='csv'){var k=Object.keys(pp[0]);return k.join(',')+'\n'+pp.map(p=>k.map(k2=>'"'+String(p[k2]).replace(/"/g,'""')+'"').join(',')).join('\n');}return pp.map(p=>'Name:    '+p.name+'\nEmail:   '+p.email+'\nPhone:   '+p.phone+'\nDOB:     '+p.dob+'\nJob:     '+p.job+' @ '+p.company+'\nAddress: '+p.street+', '+p.city+' '+p.zip).join('\n\n─────────────────────\n\n');}
        },
        { id:'fake-company', cat:'fakedata', title:'Fake Company', desc:'Business name, domain, slogan, email, and tax IDs', tags:['bulk'], related:['fake-person'],
          inputs:[{type:'slider',id:'qty',label:'Records',value:5,min:1,max:50,unit:''}],
          fn(cfg){var arr=[];for(var i=0;i<+cfg.qty;i++)arr.push(fakeCompany());return +cfg.qty===1?JSON.stringify(arr[0],null,2):JSON.stringify(arr,null,2);}
        },
        { id:'fake-ip', cat:'fakedata', title:'IPv4 / IPv6', desc:'Random IP addresses — IPv4, IPv6, or both', tags:['fast'], related:['fake-mac','fake-url'],
          inputs:[{type:'toggle',id:'ver',label:'Version',options:['IPv4','IPv6','Both'],value:'IPv4'},{type:'number',id:'qty',label:'Count',value:10,min:1,max:100}],
          fn(cfg){var out=[];for(var i=0;i<+cfg.qty;i++){if(cfg.ver==='IPv4'||cfg.ver==='Both')out.push(randInt(1,254)+'.'+randInt(0,255)+'.'+randInt(0,255)+'.'+randInt(1,254));if(cfg.ver==='IPv6'||cfg.ver==='Both'){var grp=[];for(var g=0;g<8;g++)grp.push(randHex(2));out.push(grp.join(':'));}}return out.join('\n');}
        },
        { id:'fake-mac', cat:'fakedata', title:'MAC Address', desc:'Random MAC addresses (locally administered, unicast)', tags:['fast'], related:['fake-ip'],
          inputs:[{type:'toggle',id:'sep',label:'Separator',options:[':','-','none'],value:':'},{type:'number',id:'qty',label:'Count',value:5,min:1,max:50}],
          fn(cfg){var out=[];for(var i=0;i<+cfg.qty;i++){var b=Array.from(randBytes(6)).map(x=>('0'+x.toString(16)).slice(-2).toUpperCase());b[0]=('0'+((parseInt(b[0],16)&0xfe&0xfc)|0x00).toString(16)).slice(-2).toUpperCase();out.push(cfg.sep==='none'?b.join(''):b.join(cfg.sep));}return out.join('\n');}
        },
        { id:'fake-useragent', cat:'fakedata', title:'User Agent', desc:'Realistic browser User-Agent strings for testing', tags:['fast'], related:['fake-ip','fake-url'],
          inputs:[{type:'number',id:'qty',label:'Count',value:5,min:1,max:20}],
          fn(cfg){var out=[];for(var i=0;i<+cfg.qty;i++)out.push(randItem(UA));return out.join('\n');}
        },
        { id:'fake-url', cat:'fakedata', title:'Fake URLs', desc:'Random realistic URLs with subdomains and API paths', tags:['fast'], related:['fake-ip','fake-useragent'],
          inputs:[{type:'number',id:'qty',label:'Count',value:8,min:1,max:50},{type:'toggle',id:'proto',label:'Protocol',options:['https','http','both'],value:'https'}],
          fn(cfg){var subs=['www','api','app','dev','cdn','admin','blog','shop','docs'],paths=['/users','/products','/orders','/api/v1','/posts','/search'],out=[];for(var i=0;i<+cfg.qty;i++){var sub=randItem(subs),dom=randItem(CW1).toLowerCase()+randItem(CW2).toLowerCase().replace(/\s/g,''),tld=randItem(TLDS),path=randItem(paths)+'/'+randHex(4),proto=cfg.proto==='both'?randItem(['https','http']):cfg.proto;out.push(proto+'://'+sub+'.'+dom+'.'+tld+path);}return out.join('\n');}
        },
        { id:'fake-sql', cat:'fakedata', title:'Database Seeder', desc:'Generate INSERT statements or seed data for databases', tags:['popular','bulk'], related:['fake-person','gen-sql'],
          inputs:[{type:'select',id:'table',label:'Table',options:[{v:'users',l:'users'},{v:'products',l:'products'},{v:'orders',l:'orders'},{v:'employees',l:'employees'}],value:'users'},{type:'toggle',id:'dialect',label:'Dialect',options:['MySQL','PostgreSQL','SQLite'],value:'MySQL'},{type:'slider',id:'rows',label:'Rows',value:10,min:1,max:200,unit:''},{type:'toggle',id:'fmt',label:'Export as',options:['SQL','JSON','CSV'],value:'SQL'}],
          fn(cfg){var dialect=cfg.dialect.toLowerCase(),q=dialect==='postgresql'?'"':'`',records=sqlRows(cfg.table,+cfg.rows);if(cfg.fmt==='JSON')return JSON.stringify(records,null,2);if(cfg.fmt==='CSV'){var k=Object.keys(records[0]);return k.join(',')+'\n'+records.map(r=>k.map(k=>'"'+String(r[k]).replace(/"/g,'""')+'"').join(',')).join('\n');}var k2=Object.keys(records[0]),vals=records.map(r=>'('+k2.map(k=>typeof r[k]==='number'?r[k]:"'"+String(r[k]).replace(/'/g,"''")+"'").join(', ')+')');return 'INSERT INTO '+q+cfg.table+q+' ('+k2.map(k=>q+k+q).join(', ')+')\nVALUES\n'+vals.join(',\n')+';';}
        },
        /* Structured */
        { id:'gen-json', cat:'structured', title:'JSON Generator', desc:'Fake structured JSON for users, products, invoices, API responses', tags:['popular','bulk'], related:['gen-csv','gen-xml'],
          inputs:[{type:'select',id:'type',label:'Schema',options:[{v:'user',l:'User'},{v:'product',l:'Product'},{v:'company',l:'Company'},{v:'invoice',l:'Invoice'},{v:'api',l:'API Response'}],value:'user'},{type:'number',id:'qty',label:'Records',value:3,min:1,max:100}],
          fn(cfg){var arr=[];for(var i=0;i<+cfg.qty;i++){var p=fakePerson(),c=fakeCompany(),obj;if(cfg.type==='user')obj={id:uuidV4(),name:p.name,email:p.email,phone:p.phone,age:p.age,address:{street:p.street,city:p.city,country:p.country,zip:p.zip},company:p.company,job_title:p.job,created_at:new Date().toISOString()};else if(cfg.type==='product')obj={id:uuidV4(),name:'Product '+randHex(3).toUpperCase(),price:+(randInt(100,9999)/100).toFixed(2),stock:randInt(0,500),category:randItem(['Electronics','Clothing','Books']),created_at:new Date().toISOString()};else if(cfg.type==='company')obj={id:uuidV4(),...c};else if(cfg.type==='invoice')obj={invoice_no:'INV-'+new Date().getFullYear()+'-'+String(randInt(1,9999)).padStart(4,'0'),from:{name:c.name,email:c.email},to:{name:p.name,email:p.email},total:randInt(550,2200)};else obj={success:true,status:200,data:{id:uuidV4()},meta:{request_id:nanoid(12),page:1,total:randInt(10,1000)}};arr.push(obj);}return +cfg.qty===1?JSON.stringify(arr[0],null,2):JSON.stringify(arr,null,2);}
        },
        { id:'gen-csv', cat:'structured', title:'CSV Generator', desc:'Fake CSV datasets for products, customers, employees, orders', tags:['popular','bulk'], related:['gen-json','fake-sql'],
          inputs:[{type:'select',id:'type',label:'Dataset',options:[{v:'customers',l:'Customers'},{v:'products',l:'Products'},{v:'employees',l:'Employees'},{v:'orders',l:'Orders'}],value:'customers'},{type:'slider',id:'rows',label:'Rows',value:20,min:1,max:500,unit:''}],
          fn(cfg){var lines=[];if(cfg.type==='customers'){lines.push('id,name,email,phone,city,country');for(var i=0;i<+cfg.rows;i++){var p=fakePerson();lines.push([i+1,p.name,p.email,p.phone,p.city,p.country].join(','));}}else if(cfg.type==='products'){lines.push('id,name,sku,price,stock,category');for(var i=0;i<+cfg.rows;i++)lines.push([i+1,'Product '+randHex(3).toUpperCase(),'SKU-'+randHex(4).toUpperCase(),(randInt(100,9999)/100).toFixed(2),randInt(0,500),randItem(['Electronics','Clothing','Books','Food'])].join(','));}else if(cfg.type==='employees'){lines.push('id,name,email,department,salary,hired_at');for(var i=0;i<+cfg.rows;i++){var p=fakePerson();lines.push([i+1,p.name,p.email,randItem(['Engineering','Marketing','Sales','HR','Finance']),randInt(40000,150000),randomDate()].join(','));}}else{lines.push('id,user_id,quantity,total,status,created_at');for(var i=0;i<+cfg.rows;i++)lines.push([i+1,randInt(1,100),randInt(1,10),(randInt(500,50000)/100).toFixed(2),randItem(['pending','shipped','delivered']),randomDate()].join(','));}return lines.join('\n');}
        },
        { id:'gen-xml', cat:'structured', title:'XML Generator', desc:'Sitemap XML, RSS feeds, and structured product/user XML', tags:['bulk'], related:['gen-json','gen-csv'],
          inputs:[{type:'select',id:'type',label:'Type',options:[{v:'sitemap',l:'Sitemap'},{v:'rss',l:'RSS Feed'},{v:'products',l:'Products'},{v:'users',l:'Users'}],value:'sitemap'},{type:'slider',id:'rows',label:'Entries',value:5,min:1,max:50,unit:''},{type:'text',id:'base',label:'Base URL',value:'https://example.com'}],
          fn(cfg){var n=+cfg.rows,base=cfg.base||'https://example.com',out;if(cfg.type==='sitemap'){var urls=['/','/about','/contact','/products','/blog'].concat(Array.from({length:n},(_,i)=>'/page/'+(i+1))).slice(0,n);out='<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n'+urls.map(u=>'  <url>\n    <loc>'+xe(base+u)+'</loc>\n    <lastmod>'+new Date().toISOString().slice(0,10)+'</lastmod>\n    <changefreq>weekly</changefreq>\n    <priority>0.8</priority>\n  </url>').join('\n')+'\n</urlset>';}else if(cfg.type==='rss'){out='<?xml version="1.0" encoding="UTF-8"?>\n<rss version="2.0">\n  <channel>\n    <title>Blog Feed</title>\n    <link>'+xe(base)+'</link>\n'+Array.from({length:n},(_,i)=>'    <item>\n      <title>Post '+(i+1)+'</title>\n      <link>'+xe(base+'/post/'+(i+1))+'</link>\n    </item>').join('\n')+'\n  </channel>\n</rss>';}else if(cfg.type==='products'){out='<?xml version="1.0" encoding="UTF-8"?>\n<products>\n'+Array.from({length:n},(_,i)=>'  <product id="'+(i+1)+'">\n    <name>'+xe('Product '+randHex(3).toUpperCase())+'</name>\n    <price currency="USD">'+(randInt(100,9999)/100).toFixed(2)+'</price>\n  </product>').join('\n')+'\n</products>';}else{out='<?xml version="1.0" encoding="UTF-8"?>\n<users>\n'+Array.from({length:n},(_,i)=>{var p=fakePerson();return'  <user id="'+(i+1)+'">\n    <name>'+xe(p.name)+'</name>\n    <email>'+xe(p.email)+'</email>\n  </user>';}).join('\n')+'\n</users>';}return out;}
        },
        /* Code generators */
        { id:'gen-html', cat:'code', title:'HTML Component', desc:'Tables, forms, Bootstrap cards, pricing tables, and navigation', tags:['popular'], related:['gen-css','gen-js'], preview:true,
          inputs:[{type:'select',id:'type',label:'Component',options:[{v:'table',l:'Table'},{v:'form',l:'Form'},{v:'card',l:'Bootstrap Cards'},{v:'pricing',l:'Pricing Table'},{v:'nav',l:'Navigation'}],value:'table'},{type:'text',id:'cols',label:'Columns',value:'Name,Email,Phone,Status'},{type:'number',id:'rows',label:'Rows',value:5,min:1,max:20},{type:'number',id:'cards',label:'Cards',value:3,min:1,max:6}],
          fn(cfg){var t=cfg.type,out;if(t==='table'){var cols=(cfg.cols||'Name,Email,Status').split(',').map(c=>c.trim());var rows=+cfg.rows;out='<table class="table">\n  <thead>\n    <tr>\n'+cols.map(c=>'      <th>'+c+'</th>').join('\n')+'\n    </tr>\n  </thead>\n  <tbody>\n';for(var i=0;i<rows;i++){out+='    <tr>\n'+cols.map(c=>{var p=fakePerson();var v=c.toLowerCase().includes('email')?p.email:c.toLowerCase().includes('name')?p.name:c.toLowerCase().includes('status')?randItem(['Active','Inactive','Pending']):randHex(4);return'      <td>'+v+'</td>';}).join('\n')+'\n    </tr>\n';}out+='  </tbody>\n</table>';}else if(t==='form'){var fields=(cfg.cols||'Name:text,Email:email,Message:textarea').split(',');out='<form method="POST">\n'+fields.map(f=>{var pt=f.trim().split(':'),lbl=pt[0],tp=pt[1]||'text',id2=lbl.toLowerCase().replace(/\s+/g,'-');return'  <div class="mb-3">\n    <label for="'+id2+'" class="form-label">'+lbl+'</label>\n'+(tp==='textarea'?'    <textarea id="'+id2+'" name="'+id2+'" class="form-control" rows="4"></textarea>':'    <input type="'+tp+'" id="'+id2+'" name="'+id2+'" class="form-control">')+'\n  </div>';}).join('\n')+'\n  <button type="submit" class="btn btn-primary">Submit</button>\n</form>';}else if(t==='pricing'){out='<div class="row g-4">\n'+[{n:'Starter',p:'$9',f:['5 projects','1 GB storage','Email support']},{n:'Pro',p:'$29',f:['Unlimited projects','10 GB storage','Priority support','API access']},{n:'Enterprise',p:'$99',f:['Everything in Pro','Custom integrations']}].map(pl=>'  <div class="col-md-4">\n    <div class="card text-center h-100">\n      <div class="card-header fw-bold">'+pl.n+'</div>\n      <div class="card-body">\n        <h2 class="my-3">'+pl.p+'<small>/mo</small></h2>\n        <ul class="list-unstyled">\n'+pl.f.map(f=>'          <li>&#10003; '+f+'</li>').join('\n')+'\n        </ul>\n        <a href="#" class="btn btn-primary w-100">Get Started</a>\n      </div>\n    </div>\n  </div>').join('\n')+'\n</div>';}else if(t==='card'){var pp=[];for(var i=0;i<+cfg.cards;i++)pp.push(fakePerson());out='<div class="row g-4">\n'+pp.map(p=>'  <div class="col-md-4">\n    <div class="card h-100">\n      <div class="card-body">\n        <h5 class="card-title">'+p.name+'</h5>\n        <p class="text-muted small">'+p.job+'</p>\n        <a href="mailto:'+p.email+'" class="btn btn-sm btn-primary">Contact</a>\n      </div>\n    </div>\n  </div>').join('\n')+'\n</div>';}else{out='<nav class="navbar navbar-expand-lg bg-light">\n  <div class="container"><a class="navbar-brand fw-bold" href="#">Brand</a></div>\n</nav>';}return out;}
        },
        { id:'gen-css', cat:'code', title:'CSS Generator', desc:'Gradients, shadows, glassmorphism, neumorphism, flexbox, grid', tags:['popular'], related:['gen-html','gen-js'], preview:true,
          inputs:[{type:'select',id:'type',label:'Effect',options:[{v:'gradient',l:'Linear Gradient'},{v:'radial',l:'Radial Gradient'},{v:'shadow',l:'Box Shadows'},{v:'glass',l:'Glassmorphism'},{v:'neumorphism',l:'Neumorphism'},{v:'animation',l:'CSS Animations'},{v:'flex',l:'Flexbox Layout'},{v:'grid',l:'CSS Grid'}],value:'gradient'}],
          fn(cfg){var c1='#667eea',c2='#764ba2';if(cfg.type==='gradient')return '.gradient {\n  background: '+c1+';\n  background: linear-gradient(135deg, '+c1+', '+c2+');\n}';if(cfg.type==='radial')return '.radial {\n  background: radial-gradient(circle at center, '+c1+' 0%, '+c2+' 100%);\n}';if(cfg.type==='shadow')return '.shadow-sm  { box-shadow: 0 1px 3px rgba(0,0,0,.12); }\n.shadow-md  { box-shadow: 0 4px 12px rgba(0,0,0,.15); }\n.shadow-lg  { box-shadow: 0 10px 40px rgba(0,0,0,.2); }\n.shadow-xl  { box-shadow: 0 20px 60px rgba(0,0,0,.25); }\n.shadow-inner { box-shadow: inset 0 2px 8px rgba(0,0,0,.1); }';if(cfg.type==='glass')return '.glass {\n  background: rgba(255, 255, 255, 0.15);\n  backdrop-filter: blur(12px);\n  -webkit-backdrop-filter: blur(12px);\n  border: 1px solid rgba(255, 255, 255, 0.2);\n  border-radius: 12px;\n  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);\n}';if(cfg.type==='neumorphism')return '.neumorphic {\n  background: #e0e5ec;\n  border-radius: 16px;\n  box-shadow: 8px 8px 16px rgba(0,0,0,.12), -8px -8px 16px rgba(255,255,255,.8);\n}';if(cfg.type==='animation')return '@keyframes fadeIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }\n@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }\n@keyframes spin { to { transform: rotate(360deg); } }\n.fade-in  { animation: fadeIn  0.3s ease forwards; }\n.slide-up { animation: slideUp 0.4s ease forwards; }\n.spinner  { animation: spin    1s linear infinite; }';if(cfg.type==='flex')return '.flex-container {\n  display: flex;\n  flex-wrap: wrap;\n  justify-content: space-between;\n  align-items: center;\n  gap: 16px;\n}\n.flex-item { flex: 1 1 200px; min-width: 0; }\n.flex-center { display: flex; align-items: center; justify-content: center; }';return '.grid-container {\n  display: grid;\n  grid-template-columns: repeat(3, 1fr);\n  gap: 24px;\n}\n@media (max-width: 768px) {\n  .grid-container { grid-template-columns: 1fr; }\n}';}
        },
        { id:'gen-js', cat:'code', title:'JavaScript Snippets', desc:'Fetch API, debounce, throttle, localStorage, cookies, and more', tags:['popular'], related:['gen-css','gen-html'],
          inputs:[{type:'select',id:'type',label:'Snippet',options:[{v:'fetch',l:'Fetch API'},{v:'debounce',l:'Debounce'},{v:'throttle',l:'Throttle'},{v:'localstorage',l:'LocalStorage Helper'},{v:'cookie',l:'Cookie Helper'},{v:'uuid',l:'UUID Function'},{v:'sleep',l:'Sleep / Delay'},{v:'observer',l:'Intersection Observer'}],value:'fetch'}],
          fn(cfg){var snippets={fetch:"async function fetchData(url, options = {}) {\n  try {\n    const response = await fetch(url, {\n      method: options.method || 'GET',\n      headers: { 'Content-Type': 'application/json', ...options.headers },\n      ...(options.body ? { body: JSON.stringify(options.body) } : {}),\n    });\n    if (!response.ok) throw new Error(`HTTP ${response.status}`);\n    return await response.json();\n  } catch (error) {\n    console.error('Fetch error:', error);\n    throw error;\n  }\n}",debounce:"function debounce(func, wait = 300) {\n  let timeout;\n  return function (...args) {\n    clearTimeout(timeout);\n    timeout = setTimeout(() => func.apply(this, args), wait);\n  };\n}",throttle:"function throttle(func, limit = 300) {\n  let inThrottle;\n  return function (...args) {\n    if (!inThrottle) {\n      func.apply(this, args);\n      inThrottle = true;\n      setTimeout(() => (inThrottle = false), limit);\n    }\n  };\n}",localstorage:"const storage = {\n  set(key, value) {\n    try { localStorage.setItem(key, JSON.stringify(value)); }\n    catch (e) { console.error('Storage write failed:', e); }\n  },\n  get(key, fallback = null) {\n    try {\n      const item = localStorage.getItem(key);\n      return item !== null ? JSON.parse(item) : fallback;\n    } catch { return fallback; }\n  },\n  remove(key) { localStorage.removeItem(key); },\n  has(key)    { return localStorage.getItem(key) !== null; },\n};",cookie:"const Cookies = {\n  set(name, value, days = 7) {\n    const expires = new Date(Date.now() + days * 864e5).toUTCString();\n    document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/; SameSite=Lax`;\n  },\n  get(name) {\n    return document.cookie.split('; ').reduce((acc, c) => {\n      const [k, ...v] = c.split('=');\n      return k === name ? decodeURIComponent(v.join('=')) : acc;\n    }, null);\n  },\n  delete(name) { this.set(name, '', -1); },\n};",uuid:"function generateUUID() {\n  if (crypto?.randomUUID) return crypto.randomUUID();\n  return '10000000-1000-4000-8000-100000000000'.replace(\n    /[018]/g, c =>\n      (parseInt(c) ^ crypto.getRandomValues(new Uint8Array(1))[0] & (15 >> (parseInt(c) / 4))).toString(16)\n  );\n}",sleep:"const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));\n\nasync function main() {\n  console.log('start');\n  await sleep(1000);\n  console.log('after 1s');\n}",observer:"const observer = new IntersectionObserver((entries) => {\n  entries.forEach(entry => {\n    if (entry.isIntersecting) {\n      entry.target.classList.add('visible');\n      observer.unobserve(entry.target);\n    }\n  });\n}, { threshold: 0.1 });\n\ndocument.querySelectorAll('.animate').forEach(el => observer.observe(el));"};return snippets[cfg.type]||'';}
        },
        { id:'gen-sql', cat:'code', title:'SQL Generator', desc:'CREATE TABLE, CRUD queries, indexes, and triggers', tags:['popular'], related:['fake-sql','gen-json'],
          inputs:[{type:'select',id:'type',label:'Statement',options:[{v:'create',l:'CREATE TABLE'},{v:'crud',l:'Full CRUD'},{v:'index',l:'Indexes'},{v:'trigger',l:'Trigger'}],value:'create'},{type:'select',id:'table',label:'Table',options:[{v:'users',l:'users'},{v:'products',l:'products'},{v:'orders',l:'orders'}],value:'users'},{type:'toggle',id:'dialect',label:'Dialect',options:['MySQL','PostgreSQL','SQLite'],value:'MySQL'}],
          fn(cfg){var q=cfg.dialect==='PostgreSQL'?'"':'`',di=cfg.dialect.toLowerCase(),t=cfg.table;if(cfg.type==='create'){var defs={users:[q+'id'+q+(di==='mysql'?' INT UNSIGNED AUTO_INCREMENT':di==='postgresql'?' SERIAL':' INTEGER')+' PRIMARY KEY',q+'name'+q+' VARCHAR(255) NOT NULL',q+'email'+q+' VARCHAR(255) UNIQUE NOT NULL',q+'password_hash'+q+' VARCHAR(255) NOT NULL',q+'created_at'+q+' TIMESTAMP DEFAULT CURRENT_TIMESTAMP'],products:[q+'id'+q+(di==='mysql'?' INT UNSIGNED AUTO_INCREMENT':di==='postgresql'?' SERIAL':' INTEGER')+' PRIMARY KEY',q+'name'+q+' VARCHAR(255) NOT NULL',q+'price'+q+' DECIMAL(10,2) NOT NULL',q+'stock'+q+' INT NOT NULL DEFAULT 0',q+'created_at'+q+' TIMESTAMP DEFAULT CURRENT_TIMESTAMP'],orders:[q+'id'+q+(di==='mysql'?' INT UNSIGNED AUTO_INCREMENT':di==='postgresql'?' SERIAL':' INTEGER')+' PRIMARY KEY',q+'user_id'+q+' INT NOT NULL',q+'total'+q+' DECIMAL(10,2) NOT NULL',q+'status'+q+" VARCHAR(50) DEFAULT 'pending'",q+'created_at'+q+' TIMESTAMP DEFAULT CURRENT_TIMESTAMP']};return'CREATE TABLE '+q+t+q+' (\n'+((defs[t]||defs.users).map(c=>'  '+c).join(',\n'))+'\n);';}if(cfg.type==='crud')return'-- SELECT all\nSELECT * FROM '+q+t+q+' ORDER BY '+q+'id'+q+' DESC LIMIT 20;\n\n-- SELECT one\nSELECT * FROM '+q+t+q+' WHERE '+q+'id'+q+' = :id;\n\n-- INSERT\nINSERT INTO '+q+t+q+' ('+q+'name'+q+', '+q+'email'+q+')\nVALUES (:name, :email);\n\n-- UPDATE\nUPDATE '+q+t+q+' SET '+q+'name'+q+' = :name WHERE '+q+'id'+q+' = :id;\n\n-- DELETE\nDELETE FROM '+q+t+q+' WHERE '+q+'id'+q+' = :id;';if(cfg.type==='index')return'CREATE INDEX '+q+'idx_'+t+'_email'+q+' ON '+q+t+q+' ('+q+'email'+q+');\nCREATE UNIQUE INDEX '+q+'udx_'+t+'_slug'+q+' ON '+q+t+q+' ('+q+'slug'+q+');';return di==='postgresql'?'CREATE OR REPLACE FUNCTION update_updated_at()\nRETURNS TRIGGER AS $$\nBEGIN\n  NEW.updated_at = NOW();\n  RETURN NEW;\nEND;\n$$ LANGUAGE plpgsql;\n\nCREATE TRIGGER set_updated_at\nBEFORE UPDATE ON "'+t+'"\nFOR EACH ROW EXECUTE FUNCTION update_updated_at();':'DELIMITER //\nCREATE TRIGGER `set_updated_at`\nBEFORE UPDATE ON `'+t+'`\nFOR EACH ROW\nBEGIN\n  SET NEW.updated_at = NOW();\nEND //\nDELIMITER ;';}
        },
        { id:'gen-regex', cat:'code', title:'Regex Patterns', desc:'Ready-to-use validation patterns with live test input', tags:['popular'], related:['gen-js','gen-sql'], preview:true,
          inputs:[{type:'select',id:'pattern',label:'Pattern',options:[{v:'email',l:'Email'},{v:'url',l:'URL'},{v:'phone',l:'Phone (E.164)'},{v:'password',l:'Strong Password'},{v:'username',l:'Username'},{v:'hex_color',l:'Hex Color'},{v:'ipv4',l:'IPv4'},{v:'uuid',l:'UUID'},{v:'date',l:'Date YYYY-MM-DD'},{v:'slug',l:'Slug'},{v:'zip_us',l:'US ZIP Code'},{v:'jwt',l:'JWT Token'}],value:'email'},{type:'toggle',id:'lang',label:'Language',options:['JavaScript','PHP','Python'],value:'JavaScript'}],
          fn(cfg){var PATS={email:{p:'/^[a-zA-Z0-9._%+\\-]+@[a-zA-Z0-9.\\-]+\\.[a-zA-Z]{2,}$/i',desc:'Email address',example:'user@example.com'},url:{p:'/^https?:\\/\\/[\\w\\-]+(\\.[\\w\\-]+)+([\\w.,@?^=%&:/~+#\\-]*)?$/i',desc:'HTTP/HTTPS URL',example:'https://example.com'},phone:{p:'/^\\+?[1-9]\\d{1,14}$/',desc:'E.164 phone',example:'+12025551234'},password:{p:'/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[@$!%*?&])[A-Za-z\\d@$!%*?&]{8,}$/',desc:'Strong password',example:'Str0ng!Pass'},username:{p:'/^[a-zA-Z0-9_\\-]{3,20}$/',desc:'Username',example:'john_doe'},hex_color:{p:'/^#?([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/',desc:'Hex color',example:'#ff6600'},ipv4:{p:'/^((25[0-5]|2[0-4]\\d|[01]?\\d\\d?)\\.){3}(25[0-5]|2[0-4]\\d|[01]?\\d\\d?)$/',desc:'IPv4',example:'192.168.1.1'},uuid:{p:'/^[0-9a-f]{8}-[0-9a-f]{4}-[1-7][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',desc:'UUID',example:'550e8400-e29b-41d4-a716-446655440000'},date:{p:'/^\\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\\d|3[01])$/',desc:'Date YYYY-MM-DD',example:'2026-06-27'},slug:{p:'/^[a-z0-9]+(?:-[a-z0-9]+)*$/',desc:'URL slug',example:'my-blog-post'},zip_us:{p:'/^\\d{5}(?:-\\d{4})?$/',desc:'US ZIP',example:'10001-1234'},jwt:{p:'/^[A-Za-z0-9_-]+\\.[A-Za-z0-9_-]+\\.[A-Za-z0-9_-]+$/',desc:'JWT',example:'header.payload.sig'}};var r=PATS[cfg.pattern]||PATS.email,pat=r.p.slice(1,r.p.lastIndexOf('/'));if(cfg.lang==='PHP')return'<?php\n$pattern = \''+r.p+'\';\n$test = \''+r.example+'\';\nif (preg_match($pattern, $test)) echo "Valid";\nelse echo "Invalid";\n?>';if(cfg.lang==='Python')return'import re\npattern = re.compile(r\''+pat+'\')\ntest = \''+r.example+'\'\nprint("Valid" if pattern.match(test) else "Invalid")';return'// '+r.desc+'\nconst regex = '+r.p+';\nconsole.log(regex.test(\''+r.example+'\')); // true';}
        },
        /* Utilities */
        { id:'gen-slug', cat:'utils', title:'Slug Generator', desc:'Convert any text to a URL-safe slug with transliteration', tags:['fast','popular'], related:['gen-filename','gen-username'],
          inputs:[{type:'text',id:'input',label:'Input text',value:'Hello World! My First Blog Post.'},{type:'slider',id:'max',label:'Max length',value:60,min:0,max:200,unit:'chars'}],
          fn(cfg){return slugify(cfg.input,+cfg.max)||'(empty)';}
        },
        { id:'gen-username', cat:'utils', title:'Username Generator', desc:'Creative usernames, gamer tags, and social media handles', tags:['popular'], related:['gen-slug','gen-filename'],
          inputs:[{type:'select',id:'style',label:'Style',options:[{v:'adjnoun',l:'Adj + Noun'},{v:'gamer',l:'Gamer Tag'},{v:'dev',l:'Dev Handle'},{v:'handle',l:'@Handle'}],value:'adjnoun'},{type:'number',id:'qty',label:'Count',value:10,min:1,max:50}],
          fn(cfg){var adj=['cool','super','swift','dark','mad','wild','clever','epic','fast','cyber','ninja','ghost','turbo','nova','void'],noun=['wolf','fox','hawk','bear','code','byte','node','dev','pixel','stack','cloud','nexus','core','prime','forge'];var out=[];for(var i=0;i<+cfg.qty;i++){var s;if(cfg.style==='adjnoun')s=randItem(adj)+'_'+randItem(noun);else if(cfg.style==='gamer')s=randItem(adj).toUpperCase()+randItem(noun)+randInt(10,99);else if(cfg.style==='dev')s=randItem(noun)+'_'+randHex(2);else s='@'+randItem(adj)+randItem(noun)+randInt(1,999);out.push(s);}return out.join('\n');}
        },
        { id:'gen-filename', cat:'utils', title:'Filename Generator', desc:'Safe, SEO-friendly, timestamped, or UUID-based file names', tags:['fast'], related:['gen-slug'],
          inputs:[{type:'text',id:'base',label:'Base name',value:'document'},{type:'text',id:'ext',label:'Extension',value:'.txt'},{type:'select',id:'style',label:'Style',options:[{v:'safe',l:'Safe slug'},{v:'uuid',l:'UUID'},{v:'ts',l:'Timestamp'},{v:'hash',l:'+ Hash'},{v:'nano',l:'NanoID'}],value:'safe'},{type:'number',id:'qty',label:'Count',value:5,min:1,max:20}],
          fn(cfg){var out=[];for(var i=0;i<+cfg.qty;i++){var base=slugify(cfg.base||'file'),ext=cfg.ext||'.txt',name;if(cfg.style==='uuid')name=uuidV4()+ext;else if(cfg.style==='ts'){var d=new Date(),ts=d.getFullYear()+String(d.getMonth()+1).padStart(2,'0')+String(d.getDate()).padStart(2,'0')+'_'+String(d.getHours()).padStart(2,'0')+String(d.getMinutes()).padStart(2,'0')+String(d.getSeconds()).padStart(2,'0');name=base+'_'+ts+(+cfg.qty>1?'_'+(i+1):'')+ext;}else if(cfg.style==='hash')name=base+'_'+randHex(4)+ext;else if(cfg.style==='nano')name=nanoid(12)+ext;else name=base+(+cfg.qty>1?'-'+(i+1):'')+ext;out.push(name);}return out.join('\n');}
        },
        { id:'gen-color', cat:'utils', title:'Color Palette', desc:'Random colors, shade palettes, Tailwind, and Material swatches', tags:['popular'], related:['gen-css'], preview:true,
          inputs:[{type:'toggle',id:'mode',label:'Mode',options:['random','palette','tailwind','material'],value:'random'},{type:'slider',id:'count',label:'Count (random)',value:6,min:1,max:24,unit:''}],
          fn(cfg){var colors;if(cfg.mode==='tailwind')colors=['#f87171','#fb923c','#fbbf24','#4ade80','#34d399','#22d3ee','#60a5fa','#818cf8','#a78bfa','#f472b6'];else if(cfg.mode==='material')colors=['#f44336','#e91e63','#9c27b0','#3f51b5','#2196f3','#00bcd4','#009688','#4caf50','#cddc39','#ff9800'];else if(cfg.mode==='palette'){var base=hslToHex(randInt(0,360),randInt(50,80),50);colors=colorPalette(base);}else{colors=[];for(var i=0;i<+cfg.count;i++)colors.push(hslToHex(randInt(0,360),randInt(40,80),randInt(35,65)));}return colors.join('\n');}
        },
        { id:'gen-datetime', cat:'utils', title:'Date & Time', desc:'Random dates, times, Unix timestamps, ISO 8601, and birthdays', tags:['fast'], related:['gen-filename'],
          inputs:[{type:'toggle',id:'fmt',label:'Format',options:['date','time','datetime','timestamp','iso','birthday'],value:'date'},{type:'number',id:'qty',label:'Count',value:10,min:1,max:100}],
          fn(cfg){var out=[];for(var i=0;i<+cfg.qty;i++){var d=new Date(new Date(1970,0,1).getTime()+Math.random()*(new Date(2030,11,31).getTime()-new Date(1970,0,1).getTime()));if(cfg.fmt==='date')out.push(d.toISOString().slice(0,10));else if(cfg.fmt==='time')out.push(String(randInt(0,23)).padStart(2,'0')+':'+String(randInt(0,59)).padStart(2,'0')+':'+String(randInt(0,59)).padStart(2,'0'));else if(cfg.fmt==='datetime')out.push(d.toISOString().slice(0,19).replace('T',' '));else if(cfg.fmt==='timestamp')out.push(Math.floor(d.getTime()/1000));else if(cfg.fmt==='iso')out.push(d.toISOString());else{var age=randInt(18,65);var bd=new Date(new Date().getFullYear()-age,randInt(0,11),randInt(1,28));out.push(bd.toISOString().slice(0,10)+' (Age '+age+')');}}return out.join('\n');}
        },
        { id:'gen-qr', cat:'utils', title:'QR Payload', desc:'Generate QR-ready payloads for WiFi, vCard, email, SMS, WhatsApp', tags:['popular'], related:['gen-url'], preview:true,
          inputs:[{type:'select',id:'type',label:'Type',options:[{v:'url',l:'URL'},{v:'wifi',l:'WiFi'},{v:'vcard',l:'vCard'},{v:'email',l:'Email'},{v:'sms',l:'SMS'},{v:'whatsapp',l:'WhatsApp'},{v:'geo',l:'Geo Location'}],value:'url'},{type:'text',id:'url',label:'URL',value:'https://example.com'},{type:'text',id:'ssid',label:'SSID (WiFi)',value:'MyNetwork'},{type:'text',id:'wifi_pw',label:'WiFi Password',value:'MyPassword'},{type:'text',id:'name',label:'Name (vCard)',value:'John Doe'},{type:'text',id:'phone',label:'Phone',value:'+12025551234'},{type:'text',id:'email',label:'Email',value:'john@example.com'}],
          fn(cfg){var t=cfg.type;if(t==='url')return cfg.url||'https://example.com';if(t==='wifi')return'WIFI:T:WPA;S:'+cfg.ssid+';P:'+cfg.wifi_pw+';;';if(t==='vcard')return'BEGIN:VCARD\nVERSION:3.0\nFN:'+cfg.name+'\nTEL:'+cfg.phone+'\nEMAIL:'+cfg.email+'\nEND:VCARD';if(t==='email')return'mailto:'+cfg.email+'?subject=Hello&body=Hi%20there!';if(t==='sms')return'sms:'+cfg.phone+'?body=Hello!';if(t==='whatsapp')return'https://wa.me/'+(cfg.phone||'').replace(/\D/g,'')+'?text=Hello!';if(t==='geo')return'geo:40.7128,-74.0060';}
        },
        { id:'gen-boilerplate', cat:'utils', title:'Code Boilerplate', desc:'Starter templates for PHP, Node.js, Python, and HTML', tags:['popular'], related:['gen-config','gen-js'],
          inputs:[{type:'select',id:'type',label:'Template',options:[{v:'php-api',l:'PHP REST API'},{v:'php-crud',l:'PHP CRUD Repository'},{v:'node-express',l:'Node.js + Express'},{v:'python-flask',l:'Python Flask'},{v:'python-fastapi',l:'Python FastAPI'},{v:'html-landing',l:'HTML Landing Page'}],value:'php-api'}],
          fn(cfg){var t={'php-api':'<?php\nheader(\'Content-Type: application/json\');\n$method = $_SERVER[\'REQUEST_METHOD\'];\n$uri    = trim(parse_url($_SERVER[\'REQUEST_URI\'], PHP_URL_PATH), \'/\');\n\nfunction json(array $data, int $code = 200): void {\n    http_response_code($code);\n    echo json_encode($data);\n    exit;\n}\n\nmatch (true) {\n    $method === \'GET\'  => json([\'data\' => [], \'meta\' => [\'total\' => 0]]),\n    $method === \'POST\' => json([\'data\' => json_decode(file_get_contents(\'php://input\'),true), \'message\' => \'Created\'], 201),\n    default => json([\'error\' => \'Not Found\'], 404),\n};','node-express':"const express = require('express');\nconst app = express();\napp.use(express.json());\n\napp.get('/api/users', (req, res) => {\n  res.json({ data: [], meta: { total: 0, page: 1 } });\n});\n\napp.post('/api/users', (req, res) => {\n  res.status(201).json({ data: { id: 1, ...req.body } });\n});\n\napp.use((err, req, res, next) => {\n  res.status(500).json({ error: 'Internal Server Error' });\n});\n\napp.listen(3000, () => console.log('Server on :3000'));","python-flask":"from flask import Flask, jsonify, request\napp = Flask(__name__)\n\n@app.get('/api/users')\ndef get_users():\n    return jsonify({'data': [], 'meta': {'total': 0}})\n\n@app.post('/api/users')\ndef create_user():\n    return jsonify({'data': request.get_json()}), 201\n\nif __name__ == '__main__':\n    app.run(debug=True, port=5000)","python-fastapi":"from fastapi import FastAPI\nfrom pydantic import BaseModel\napp = FastAPI()\n\nclass UserIn(BaseModel):\n    name: str\n    email: str\n\n@app.get('/users')\nasync def get_users():\n    return []\n\n@app.post('/users', status_code=201)\nasync def create_user(user: UserIn):\n    return {'id': 1, **user.dict()}","html-landing":'<!DOCTYPE html>\n<html lang="en">\n<head>\n  <meta charset="UTF-8">\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n  <title>Landing Page</title>\n  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">\n</head>\n<body>\n  <nav class="navbar navbar-dark bg-dark">\n    <div class="container"><a class="navbar-brand fw-bold" href="#">Brand</a></div>\n  </nav>\n  <section style="padding:120px 0;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;text-align:center;">\n    <div class="container">\n      <h1 class="display-4 fw-bold mb-3">Build Something Amazing</h1>\n      <a href="#" class="btn btn-light btn-lg">Get Started Free</a>\n    </div>\n  </section>\n</body>\n</html>','php-crud':'<?php\nclass UserRepository {\n    public function __construct(private PDO $db) {}\n    public function all(): array { return $this->db->query(\'SELECT * FROM users\')->fetchAll(PDO::FETCH_ASSOC); }\n    public function find(int $id): ?array { $s=$this->db->prepare(\'SELECT * FROM users WHERE id=?\');$s->execute([$id]);return$s->fetch(PDO::FETCH_ASSOC)?:null; }\n    public function create(array $d): int { $s=$this->db->prepare(\'INSERT INTO users (name,email) VALUES (?,?)\');$s->execute([$d[\'name\'],$d[\'email\']]);return(int)$this->db->lastInsertId(); }\n    public function update(int $id, array $d): bool { return$this->db->prepare(\'UPDATE users SET name=?,email=? WHERE id=?\')->execute([$d[\'name\'],$d[\'email\'],$id]); }\n    public function delete(int $id): bool { return$this->db->prepare(\'DELETE FROM users WHERE id=?\')->execute([$id]); }\n}'};return t[cfg.type]||'';}
        },
        { id:'compose',    cat:'power', title:'Data Composer',         desc:'Build custom JSON objects by mixing UUID, names, emails, passwords, and other generators — export as JSON, CSV or SQL', tags:['popular','bulk'], related:['gen-json','fake-person'],
          inputs:[], fn:function(cfg){return cfg._result||'Click Generate to compose records';} },
        { id:'enricher',   cat:'power', title:'CSV / JSON Enricher',   desc:'Paste your existing CSV or JSON data and add generated columns: UUID, fake names, emails, timestamps, and more', tags:['bulk'], related:['gen-csv','fake-sql'],
          inputs:[], fn:function(cfg){return cfg._result||'Paste data then click Enrich';} },
        { id:'postman',    cat:'power', title:'Postman Collection',     desc:'Generate a complete Postman/Insomnia-ready collection with CRUD endpoints, auth headers, and sample request bodies', tags:['popular'], related:['gen-json','gen-sql'],
          inputs:[
            {type:'text',  id:'base_url',  label:'Base URL',       value:'https://api.example.com'},
            {type:'text',  id:'resource',  label:'Resource name',  value:'users'},
            {type:'toggle',id:'auth',      label:'Auth header',    options:['None','Bearer','API Key'],value:'Bearer'},
            {type:'toggle',id:'format',    label:'Output format',  options:['Postman v2.1','Insomnia v4'],value:'Postman v2.1'},
          ],
          fn:function(cfg){
            var base=(cfg.base_url||'https://api.example.com').replace(/\/$/,'');
            var res=cfg.resource||'users';
            var name=res.charAt(0).toUpperCase()+res.slice(1);
            var single=name.replace(/s$/,'');
            var fakeBody=JSON.stringify({id:'{{$guid}}',name:'{{$randomFullName}}',email:'{{$randomEmail}}',created_at:'{{$isoTimestamp}}'},null,2);
            function authHeaders(arr){if(cfg.auth==='Bearer')arr.push({key:'Authorization',value:'Bearer {{token}}'});if(cfg.auth==='API Key')arr.push({key:'X-API-Key',value:'{{api_key}}'});return arr;}
            if(cfg.format==='Insomnia v4'){
              var resources=[
                {_id:'req_list',_type:'request',name:'List '+name,method:'GET',url:base+'/'+res,headers:authHeaders([])},
                {_id:'req_get', _type:'request',name:'Get '+single, method:'GET',url:base+'/'+res+'/:id',headers:authHeaders([])},
                {_id:'req_post',_type:'request',name:'Create '+single,method:'POST',url:base+'/'+res,headers:authHeaders([{name:'Content-Type',value:'application/json'}]),body:{mimeType:'application/json',text:fakeBody}},
                {_id:'req_put', _type:'request',name:'Update '+single,method:'PUT',url:base+'/'+res+'/:id',headers:authHeaders([{name:'Content-Type',value:'application/json'}]),body:{mimeType:'application/json',text:fakeBody}},
                {_id:'req_del', _type:'request',name:'Delete '+single,method:'DELETE',url:base+'/'+res+'/:id',headers:authHeaders([])},
              ];
              return JSON.stringify({_type:'export',__export_format:4,__export_date:new Date().toISOString(),__export_source:'dev-generator-toolkit',resources:resources},null,2);
            }
            var items=[
              {name:'List '+name,request:{method:'GET',header:authHeaders([]),url:{raw:base+'/'+res,host:[base],path:[res]}}},
              {name:'Get '+single,request:{method:'GET',header:authHeaders([]),url:{raw:base+'/'+res+'/:id',host:[base],path:[res,':id'],variable:[{key:'id',value:'1'}]}}},
              {name:'Create '+single,request:{method:'POST',header:authHeaders([{key:'Content-Type',value:'application/json'}]),body:{mode:'raw',raw:fakeBody,options:{raw:{language:'json'}}},url:{raw:base+'/'+res,host:[base],path:[res]}}},
              {name:'Update '+single,request:{method:'PUT',header:authHeaders([{key:'Content-Type',value:'application/json'}]),body:{mode:'raw',raw:fakeBody,options:{raw:{language:'json'}}},url:{raw:base+'/'+res+'/:id',host:[base],path:[res,':id'],variable:[{key:'id',value:'1'}]}}},
              {name:'Delete '+single,request:{method:'DELETE',header:authHeaders([]),url:{raw:base+'/'+res+'/:id',host:[base],path:[res,':id'],variable:[{key:'id',value:'1'}]}}},
            ];
            return JSON.stringify({info:{name:name+' API',schema:'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'},item:items},null,2);
          }
        },
        { id:'gen-config', cat:'utils', title:'Config File Generator', desc:'.env, .gitignore, .htaccess, Dockerfile, nginx.conf, and more', tags:['popular'], related:['gen-boilerplate'],
          inputs:[{type:'select',id:'type',label:'File',options:[{v:'.env',l:'.env'},{v:'.gitignore',l:'.gitignore'},{v:'.htaccess',l:'.htaccess'},{v:'dockerfile',l:'Dockerfile'},{v:'compose',l:'docker-compose.yml'},{v:'nginx',l:'nginx.conf'},{v:'package',l:'package.json'},{v:'composer',l:'composer.json'}],value:'.env'}],
          fn(cfg){var files={'.env':'APP_NAME="My Application"\nAPP_ENV=development\nAPP_DEBUG=true\nAPP_URL=http://localhost:8000\nAPP_KEY='+randHex(32)+'\n\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=3306\nDB_DATABASE=myapp\nDB_USERNAME=root\nDB_PASSWORD=\n\nCACHE_DRIVER=file\nSESSION_DRIVER=file\n\nMAIL_HOST=smtp.mailtrap.io\nMAIL_PORT=2525','.gitignore':'# Dependencies\nnode_modules/\nvendor/\n\n# Build\ndist/\nbuild/\n.next/\n\n# Environment\n.env\n.env.local\n.env.*.local\n\n# Editor\n.idea/\n.vscode/\n.DS_Store\n\n# Logs\n*.log','.htaccess':'Options -Indexes\nRewriteEngine On\nRewriteBase /\n\n# Front controller\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule ^ index.php [L]\n\n# Security headers\nHeader always set X-Frame-Options "SAMEORIGIN"\nHeader always set X-Content-Type-Options "nosniff"\n\n<FilesMatch "\\.(css|js|png|jpg|svg|woff2)$">\n  Header set Cache-Control "public, max-age=31536000"\n</FilesMatch>',dockerfile:'FROM node:20-alpine\nWORKDIR /app\nCOPY package*.json ./\nRUN npm ci --only=production\nCOPY . .\nRUN addgroup -S app && adduser -S app -G app\nUSER app\nEXPOSE 3000\nCMD ["node", "index.js"]',compose:"version: '3.8'\nservices:\n  app:\n    build: .\n    restart: unless-stopped\n    ports:\n      - \"3000:3000\"\n    environment:\n      - NODE_ENV=production\n    depends_on:\n      db:\n        condition: service_healthy\n  db:\n    image: postgres:16-alpine\n    restart: unless-stopped\n    environment:\n      POSTGRES_DB: myapp\n      POSTGRES_USER: postgres\n      POSTGRES_PASSWORD: secret\n    volumes:\n      - pgdata:/var/lib/postgresql/data\nvolumes:\n  pgdata:",nginx:'server {\n    listen 80;\n    server_name example.com;\n    return 301 https://$server_name$request_uri;\n}\n\nserver {\n    listen 443 ssl http2;\n    server_name example.com;\n\n    root /var/www/html/public;\n    index index.php;\n\n    location / { try_files $uri $uri/ /index.php?$query_string; }\n\n    location ~ \\.php$ {\n        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;\n        include fastcgi_params;\n        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;\n    }\n}',package:JSON.stringify({name:'my-app',version:'1.0.0',scripts:{start:'node index.js',dev:'nodemon index.js',test:'jest'},dependencies:{express:'^4.18.2',dotenv:'^16.3.1'},devDependencies:{nodemon:'^3.0.1'}},null,2),composer:JSON.stringify({name:'vendor/my-app',type:'project',require:{php:'>=8.2'},'require-dev':{'phpunit/phpunit':'^11.0'},autoload:{'psr-4':{'App\\\\':'src/'}}},null,2)};return files[cfg.type]||'';}
        },
    ];

    /* ── CATEGORY METADATA ────────────────────────────────────────── */
    var CATS = {
        uuid:       { label:'UUID & Identifiers',  id:'cat-uuid' },
        passwords:  { label:'Passwords & Secrets', id:'cat-passwords' },
        random:     { label:'Random Data',         id:'cat-random' },
        lorem:      { label:'Lorem Ipsum',         id:'cat-lorem' },
        fakedata:   { label:'Fake Data',           id:'cat-fakedata' },
        structured: { label:'Structured Data',     id:'cat-structured' },
        code:       { label:'Code Generators',     id:'cat-code' },
        utils:      { label:'Utilities',           id:'cat-utils' },
        power:      { label:'Power Tools',         id:'cat-power' },
    };

    function catIcon(cat) {
        var paths={uuid:'<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8" cy="12" r="1" fill="currentColor"/><circle cx="12" cy="12" r="1" fill="currentColor"/><circle cx="16" cy="12" r="1" fill="currentColor"/>',passwords:'<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/><circle cx="12" cy="16" r="1" fill="currentColor"/>',random:'<path d="M2 18h1.4c1.3 0 2.5-.6 3.3-1.7l6.1-8.6c.7-1.1 2-1.7 3.3-1.7H22"/><path d="m18 2 4 4-4 4"/><path d="M2 6h1.9c1.5 0 2.9.9 3.6 2.2"/><path d="m18 14 4 4-4 4"/>',lorem:'<line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="13" y2="18"/>',fakedata:'<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',structured:'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',code:'<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',utils:'<circle cx="12" cy="12" r="3"/><path d="M6.3 6.3a8 8 0 1 0 11.31 0"/>',power:'<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>'};
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'+paths[cat]+'</svg>';
    }
    function toolIcon(id) {
        var icons={'uuid-v4':'<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8" cy="12" r="1" fill="currentColor"/><circle cx="12" cy="12" r="1" fill="currentColor"/><circle cx="16" cy="12" r="1" fill="currentColor"/>','uuid-v7':'<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 7v5l3 3"/>','uuid-validate':'<path d="M20 6 9 17l-5-5"/>','ulid':'<path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/><line x1="13" y1="6" x2="21" y2="6"/><line x1="13" y1="12" x2="21" y2="12"/><line x1="13" y1="18" x2="21" y2="18"/>','nanoid':'<path d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>','password':'<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>','passphrase':'<path d="M17 6.1H3"/><path d="M21 12.1H3"/><path d="M15.1 18H3"/>','api-key':'<path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>','jwt-secret':'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>','gen-json':'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h8M8 17h5"/>','gen-csv':'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>','gen-xml':'<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>','gen-html':'<path d="M4 3h16l-2 14-6 2-6-2L4 3z"/>','gen-css':'<circle cx="12" cy="12" r="10"/><path d="M8 12s1.5-2 4-2 4 2 4 2"/>','gen-js':'<rect x="2" y="2" width="20" height="20" rx="3"/><path d="M14 17v-5l3 5v-5M10 12v5c0 1.1-.9 2-2 2s-2-.9-2-2"/>','gen-sql':'<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>','gen-regex':'<path d="M12 2v8"/><path d="m4.93 10.93 5.66 5.66"/><path d="M2 18h8"/><path d="M14 18h8"/>','gen-slug':'<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>','gen-color':'<circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125C12.9 18.845 12.75 18.48 12.75 18a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/>','gen-datetime':'<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>','gen-qr':'<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="5" y="5" width="3" height="3" fill="currentColor"/><rect x="16" y="5" width="3" height="3" fill="currentColor"/><rect x="5" y="16" width="3" height="3" fill="currentColor"/>','gen-boilerplate':'<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>','gen-config':'<circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M4.93 4.93l1.41 1.41M12 2v2M12 20v2M2 12h2M20 12h2"/>','fake-sql':'<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>','gen-username':'<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><line x1="17" y1="11" x2="22" y2="11"/>','gen-filename':'<path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/>'};
        var def='<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>';
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'+(icons[id]||def)+'</svg>';
    }

    /* ── STATE ────────────────────────────────────────────────────── */
    var activeTool = null;
    var activeTab  = 'output'; // 'output' | 'preview'
    var _hooks     = { onOpen: null, onGenerate: null };

    /* ── Toast ───────────────────────────────────────────────────── */
    var _toastT;
    function toast(msg) {
        var t=document.getElementById('dgt-toast');if(!t)return;
        t.querySelector('.dgt-toast-msg').textContent=msg;
        t.classList.add('show');clearTimeout(_toastT);
        _toastT=setTimeout(()=>t.classList.remove('show'),2200);
    }
    function copyText(text) {
        if(!text){toast('Nothing to copy');return;}
        if(navigator.clipboard)navigator.clipboard.writeText(text).then(()=>toast('✓ Copied to clipboard'));
        else{var ta=document.createElement('textarea');ta.value=text;ta.style.cssText='position:fixed;opacity:0';document.body.appendChild(ta);ta.select();document.execCommand('copy');document.body.removeChild(ta);toast('✓ Copied!');}
    }
    function download(content,filename,mime) {
        var blob=new Blob([content],{type:mime||'text/plain;charset=utf-8'});
        var url=URL.createObjectURL(blob),a=document.createElement('a');
        a.href=url;a.download=filename;document.body.appendChild(a);a.click();document.body.removeChild(a);URL.revokeObjectURL(url);toast('↓ Downloaded');
    }

    /* ── Show/hide output vs empty ───────────────────────────────── */
    function setOutput(value) {
        var ta=document.getElementById('dgt-ws-output');
        var emp=document.querySelector('#dgt-workspace .dgt-empty');
        if(!ta)return;
        ta.value=value||'';
        var hasContent=value&&value.trim().length>0;
        ta.style.display=hasContent?'block':'none';
        if(emp)emp.style.display=hasContent?'none':'flex';
    }

    /* ── PREVIEW RENDERERS ───────────────────────────────────────── */
    var previewRenderers = {

        /* Color palette — visual swatches */
        'gen-color': function(result) {
            var colors=result.trim().split('\n').filter(Boolean);
            var html='<div class="dgt-swatch-grid">';
            colors.forEach(function(hex,i){
                var rgb=hexToRgb(hex.trim());
                var rgbStr='rgb('+rgb.r+','+rgb.g+','+rgb.b+')';
                html+='<div class="dgt-swatch" style="animation-delay:'+(i*0.05)+'s" onclick="DGT.copy(\''+hex.trim()+'\')" title="Click to copy">'
                    +'<div class="dgt-swatch-color" style="background:'+hex.trim()+'"></div>'
                    +'<div class="dgt-swatch-info">'
                    +'<div class="dgt-swatch-hex">'+hex.trim().toUpperCase()+'</div>'
                    +'<div class="dgt-swatch-rgb">'+rgbStr+'</div>'
                    +'</div></div>';
            });
            html+='</div>';
            return {html:html,title:'Color Preview — click any swatch to copy'};
        },

        /* CSS — visual effect demos */
        'gen-css': function(result, cfg) {
            var t=cfg&&cfg.type||'gradient';
            var c1='#667eea',c2='#764ba2';
            var html='';
            if(t==='gradient'){
                html='<div style="display:flex;flex-direction:column;gap:10px">';
                var dirs=['to right','135deg','to bottom','45deg','90deg','225deg'];
                var labels=['→ Right','↘ 135°','↓ Bottom','↗ 45°','↑ 90°','↙ 225°'];
                dirs.forEach(function(d,i){
                    html+='<div class="dgt-css-variant" style="animation-delay:'+(i*0.04)+'s">'
                        +'<div class="dgt-css-variant-box" style="background:linear-gradient('+d+','+c1+','+c2+');color:#fff">'+labels[i]+'</div>'
                        +'<div class="dgt-css-variant-label">linear-gradient('+d+')</div>'
                        +'</div>';
                });
                html+='</div>';
            } else if(t==='shadow'){
                var shadows=[['sm','0 1px 3px rgba(0,0,0,.12)'],['md','0 4px 12px rgba(0,0,0,.15)'],['lg','0 10px 40px rgba(0,0,0,.2)'],['xl','0 20px 60px rgba(0,0,0,.25)']];
                html='<div style="display:flex;flex-direction:column;gap:16px;padding:8px">';
                shadows.forEach(function(s){html+='<div style="background:var(--color-surface);border-radius:8px;padding:16px 20px;box-shadow:'+s[1]+';font-size:12px;font-family:var(--font-mono)">shadow-'+s[0]+'</div>';});
                html+='</div>';
            } else if(t==='glass'){
                html='<div style="background:linear-gradient(135deg,'+c1+','+c2+');height:140px;border-radius:8px;display:flex;align-items:center;justify-content:center"><div style="background:rgba(255,255,255,.15);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.2);border-radius:12px;padding:20px 32px;color:#fff;font-weight:600;font-size:14px;box-shadow:0 8px 32px rgba(0,0,0,.1)">Glassmorphism Effect</div></div>';
            } else if(t==='neumorphism'){
                html='<div style="background:#e0e5ec;padding:24px;border-radius:8px;display:flex;gap:20px;justify-content:center;align-items:center"><div style="background:#e0e5ec;border-radius:16px;padding:20px 32px;box-shadow:8px 8px 16px rgba(0,0,0,.12),-8px -8px 16px rgba(255,255,255,.8);font-size:13px;font-weight:600;color:#555">Raised</div><div style="background:#e0e5ec;border-radius:16px;padding:20px 32px;box-shadow:inset 8px 8px 16px rgba(0,0,0,.12),inset -8px -8px 16px rgba(255,255,255,.8);font-size:13px;font-weight:600;color:#555">Inset</div></div>';
            } else if(t==='animation'){
                html='<div style="display:flex;gap:12px;flex-wrap:wrap;padding:8px"><div style="background:var(--color-primary);color:var(--color-primary-text);padding:12px 20px;border-radius:8px;animation:fadeIn .5s ease;font-size:13px;font-weight:600">fadeIn</div><div style="background:var(--color-primary);color:var(--color-primary-text);padding:12px 20px;border-radius:8px;animation:slideUp .5s ease;font-size:13px;font-weight:600">slideUp</div></div><style>@keyframes fadeIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}@keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}</style>';
            } else if(t==='flex'){
                html='<div style="display:flex;flex-wrap:wrap;gap:8px;padding:8px">'+'ABCDE'.split('').map(l=>'<div style="flex:1 1 80px;background:var(--color-primary);color:#fff;padding:16px;border-radius:6px;text-align:center;font-weight:700;font-size:14px">'+l+'</div>').join('')+'</div>';
            } else if(t==='grid'){
                html='<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;padding:8px">'+'ABCDEFGHI'.split('').map(l=>'<div style="background:var(--color-primary-light);border:1px solid var(--color-primary);color:var(--color-primary);padding:16px;border-radius:6px;text-align:center;font-weight:700">'+l+'</div>').join('')+'</div>';
            } else {
                html='<div style="background:var(--color-surface-alt);border-radius:8px;padding:24px;display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);font-size:13px">Generate to preview</div>';
            }
            return {html:html,title:'CSS Visual Preview'};
        },

        /* HTML — rendered iframe */
        'gen-html': function(result) {
            var doc='<!DOCTYPE html><html><head><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"><style>body{padding:16px;font-family:system-ui,sans-serif;font-size:14px}</style></head><body>'+result+'</body></html>';
            var html='<iframe class="dgt-html-iframe" srcdoc="'+result.replace(/"/g,'&quot;').replace(/'/g,'&#39;')+'" sandbox="allow-same-origin" title="HTML Preview" style="border:none;width:100%;min-height:240px;background:#fff;border-radius:6px"></iframe>';
            return {html:html,title:'Rendered HTML Preview'};
        },

        /* Regex — live test input */
        'gen-regex': function(result, cfg) {
            var PATS={email:{p:'^[a-zA-Z0-9._%+\\-]+@[a-zA-Z0-9.\\-]+\\.[a-zA-Z]{2,}$',flags:'i',example:'user@example.com'},url:{p:'^https?:\\/\\/[\\w\\-]+(\\.[\\w\\-]+)+',flags:'i',example:'https://example.com'},phone:{p:'^\\+?[1-9]\\d{1,14}$',flags:'',example:'+12025551234'},password:{p:'^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[@$!%*?&])[A-Za-z\\d@$!%*?&]{8,}$',flags:'',example:'Str0ng!Pass'},username:{p:'^[a-zA-Z0-9_\\-]{3,20}$',flags:'',example:'john_doe'},hex_color:{p:'^#?([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$',flags:'',example:'#ff6600'},ipv4:{p:'^((25[0-5]|2[0-4]\\d|[01]?\\d\\d?)\\.){3}(25[0-5]|2[0-4]\\d|[01]?\\d\\d?)$',flags:'',example:'192.168.1.1'},uuid:{p:'^[0-9a-f]{8}-[0-9a-f]{4}-[1-7][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$',flags:'i',example:'550e8400-e29b-41d4-a716-446655440000'},date:{p:'^\\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\\d|3[01])$',flags:'',example:'2026-06-27'},slug:{p:'^[a-z0-9]+(?:-[a-z0-9]+)*$',flags:'',example:'my-blog-post'},zip_us:{p:'^\\d{5}(?:-\\d{4})?$',flags:'',example:'10001-1234'},jwt:{p:'^[A-Za-z0-9_-]+\\.[A-Za-z0-9_-]+\\.[A-Za-z0-9_-]+$',flags:'',example:'a.b.c'}};
            var sel=cfg&&cfg.pattern||'email';
            var pat=PATS[sel]||PATS.email;
            var eg=pat.example;
            var html='<div class="dgt-regex-test">'
                +'<label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted);display:block;margin-bottom:6px">Live Test</label>'
                +'<input id="dgt-regex-live-input" type="text" value="'+eg+'" oninput="DGT.liveRegex(\''+sel+'\')" placeholder="Type to test…">'
                +'<div id="dgt-regex-live-badge" class="dgt-regex-badge match">✓ Match</div>'
                +'</div>'
                +'<div style="margin-top:12px"><label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted);display:block;margin-bottom:6px">Pattern</label>'
                +'<div class="dgt-regex-pat">'+pat.p.replace(/</g,'&lt;')+'</div></div>'
                +'<div style="margin-top:8px"><label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted);display:block;margin-bottom:6px">Valid Examples</label>'
                +'<div style="font-family:var(--font-mono);font-size:12px;color:var(--color-text-muted)">'+eg+'</div>'
                +'</div>';
            return {html:html,title:'Live Regex Tester'};
        },

        /* Password — strength meter + display */
        'password': function(result) {
            var lines=result.trim().split('\n').filter(Boolean);
            var pw=lines[0]||'';
            var str=pwStrength(pw);
            var colors=['','#ef4444','#f59e0b','#3b82f6','#22c55e'];
            var labels=['','Weak','Fair','Good','Strong'];
            var color=colors[str]||'#94a3b8';
            var html='<div class="dgt-pw-display" style="color:'+color+'">'+pw+'</div>'
                +'<div class="dgt-strength-bar">';
            for(var i=1;i<=4;i++)html+='<div class="dgt-strength-seg" style="background:'+(i<=str?color:null)+'"></div>';
            html+='</div>'
                +'<div class="dgt-strength-label" style="color:'+color+'">'+(labels[str]||'—')+'</div>'
                +'<div class="dgt-pw-stats">'
                +'<div class="dgt-pw-stat"><div class="dgt-pw-stat-val">'+pw.length+'</div><div class="dgt-pw-stat-lab">Length</div></div>'
                +'<div class="dgt-pw-stat"><div class="dgt-pw-stat-val">'+(lines.length)+'</div><div class="dgt-pw-stat-lab">Generated</div></div>'
                +'<div class="dgt-pw-stat"><div class="dgt-pw-stat-val">'+(/[A-Z]/.test(pw)?'✓':'✗')+'</div><div class="dgt-pw-stat-lab">Uppercase</div></div>'
                +'<div class="dgt-pw-stat"><div class="dgt-pw-stat-val">'+(/[^A-Za-z0-9]/.test(pw)?'✓':'✗')+'</div><div class="dgt-pw-stat-lab">Symbols</div></div>'
                +'</div>';
            return {html:html,title:'Password Strength Analysis'};
        },

        /* Lorem — formatted text */
        'lorem-classic': function(result) { return loremPreview(result); },
        'lorem-dev':     function(result) { return loremPreview(result); },
        'lorem-corp':    function(result) { return loremPreview(result); },
        'lorem-bacon':   function(result) { return loremPreview(result); },
        'lorem-hipster': function(result) { return loremPreview(result); },
        'lorem-pirate':  function(result) { return loremPreview(result); },

        /* UUID — formatted list */
        'uuid-v4':  function(result) { return uuidPreview(result); },
        'uuid-v7':  function(result) { return uuidPreview(result); },
        'ulid':     function(result) { return uuidPreview(result); },
        'nanoid':   function(result) { return uuidPreview(result); },

        /* UUID validate */
        'uuid-validate': function(result) {
            var isValid=result.startsWith('✓');
            var html='<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:12px;padding:24px">'
                +'<div style="font-size:48px">'+(isValid?'✅':'❌')+'</div>'
                +'<div style="font-size:15px;font-weight:700;color:'+(isValid?'var(--color-success)':'var(--color-danger)')+'">'+( isValid?'Valid UUID':'Invalid UUID')+'</div>'
                +'<div style="font-family:var(--font-mono);font-size:12px;color:var(--color-text-muted);text-align:center;word-break:break-all">'+result.replace(/[✓✗]/,'').replace('Valid UUID ','').replace('Invalid UUID','').trim()+'</div>'
                +'</div>';
            return {html:html,title:'Validation Result'};
        },

        /* QR — preview via qrserver.com */
        'gen-qr': function(result) {
            var encoded=encodeURIComponent(result.trim().slice(0,300));
            var html='<div style="display:flex;flex-direction:column;align-items:center;gap:12px;padding:8px">'
                +'<img src="https://api.qrserver.com/v1/create-qr-code/?data='+encoded+'&size=180x180&margin=6" alt="QR Code" style="border-radius:8px;border:1px solid var(--color-border)" onerror="this.style.display=\'none\'">'
                +'<div style="font-size:11px;color:var(--color-text-muted);text-align:center;max-width:180px;word-break:break-all;font-family:var(--font-mono)">'+result.trim().slice(0,80)+(result.length>80?'…':'')+'</div>'
                +'<div style="font-size:10px;color:var(--color-text-subtle)">Via api.qrserver.com</div>'
                +'</div>';
            return {html:html,title:'QR Code Preview'};
        },
    };

    function loremPreview(result) {
        var paras=result.trim().split('\n\n').filter(Boolean);
        var html='<div class="dgt-lorem-preview">'+paras.map(p=>'<p>'+p+'</p>').join('')+'</div>';
        return {html:html,title:'Typography Preview'};
    }
    function uuidPreview(result) {
        var lines=result.trim().split('\n').filter(Boolean);
        var html='<div class="dgt-uuid-display">';
        lines.forEach(function(u,i){
            html+='<div class="dgt-uuid-item" style="animation-delay:'+(i*0.04)+'s" onclick="DGT.copy(\''+u+'\')">'
                +'<span class="dgt-uuid-val">'+u+'</span>'
                +'<svg class="dgt-uuid-copy" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>'
                +'</div>';
        });
        html+='</div>';
        return {html:html,title:'Click any row to copy'};
    }

    /* ── Render preview ──────────────────────────────────────────── */
    function renderPreview(toolId, result, cfg) {
        var renderer=previewRenderers[toolId];
        var pb=document.getElementById('dgt-preview-body');
        var ph=document.getElementById('dgt-preview-head-label');
        if(!pb)return;
        if(!renderer||!result||!result.trim()){
            pb.innerHTML='<div class="dgt-preview-empty"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg><p>Generate first, then switch to Preview</p></div>';
            if(ph)ph.textContent='Preview';
            return;
        }
        var out=renderer(result,cfg);
        pb.innerHTML=out.html;
        if(ph)ph.textContent=out.title||'Preview';
    }

    /* ── Live regex test ─────────────────────────────────────────── */
    function liveRegex(patKey) {
        var PATS={email:{p:'^[a-zA-Z0-9._%+\\-]+@[a-zA-Z0-9.\\-]+\\.[a-zA-Z]{2,}$',flags:'i'},url:{p:'^https?:\\/\\/[\\w\\-]+(\\.[\\w\\-]+)+',flags:'i'},phone:{p:'^\\+?[1-9]\\d{1,14}$',flags:''},password:{p:'^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[@$!%*?&])[A-Za-z\\d@$!%*?&]{8,}$',flags:''},username:{p:'^[a-zA-Z0-9_\\-]{3,20}$',flags:''},hex_color:{p:'^#?([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$',flags:''},ipv4:{p:'^((25[0-5]|2[0-4]\\d|[01]?\\d\\d?)\\.){3}(25[0-5]|2[0-4]\\d|[01]?\\d\\d?)$',flags:''},uuid:{p:'^[0-9a-f]{8}-[0-9a-f]{4}-[1-7][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$',flags:'i'},date:{p:'^\\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\\d|3[01])$',flags:''},slug:{p:'^[a-z0-9]+(?:-[a-z0-9]+)*$',flags:''},zip_us:{p:'^\\d{5}(?:-\\d{4})?$',flags:''},jwt:{p:'^[A-Za-z0-9_-]+\\.[A-Za-z0-9_-]+\\.[A-Za-z0-9_-]+$',flags:''}};
        var inp=document.getElementById('dgt-regex-live-input');
        var badge=document.getElementById('dgt-regex-live-badge');
        if(!inp||!badge)return;
        var val=inp.value;
        var pat=PATS[patKey]||PATS.email;
        try{var rx=new RegExp(pat.p,pat.flags),ok=rx.test(val);badge.className='dgt-regex-badge '+(ok?'match':'no-match');badge.textContent=ok?'✓ Match':'✗ No match';}catch(e){}
    }

    /* ── Switch tab ──────────────────────────────────────────────── */
    function switchTab(tab) {
        activeTab=tab;
        document.querySelectorAll('.dgt-out-tab').forEach(t=>t.classList.remove('active'));
        document.querySelectorAll('.dgt-out-tab-panel').forEach(p=>p.classList.remove('active'));
        var btn=document.getElementById('dgt-tab-'+tab);
        var panel=document.getElementById('dgt-panel-'+tab);
        if(btn)btn.classList.add('active');
        if(panel)panel.classList.add('active');
        // If switching to preview and we have output, render preview
        if(tab==='preview'&&activeTool){
            var ta=document.getElementById('dgt-ws-output');
            var cfg=activeTool?readConfig(activeTool):{};
            renderPreview(activeTool.id,ta&&ta.value,cfg);
        }
    }

    /* ── Render card grid ────────────────────────────────────────── */
    function renderGrid() {
        var container=document.getElementById('dgt-categories');
        if(!container)return;
        var html='';
        var order=['uuid','passwords','random','lorem','fakedata','structured','code','utils'];
        var catDelay=0;
        order.forEach(function(catKey){
            var tools=TOOLS.filter(t=>t.cat===catKey);
            if(!tools.length)return;
            var meta=CATS[catKey];
            html+='<section class="dgt-category" id="'+meta.id+'" data-cat="'+catKey+'" style="animation-delay:'+(catDelay*0.05)+'s">';
            html+='<div class="dgt-cat-header" onclick="DGT.toggleCat(\''+meta.id+'\')" role="button" aria-expanded="true">';
            html+='<span class="dgt-cat-icon">'+catIcon(catKey)+'</span>';
            html+='<span class="dgt-cat-title">'+meta.label+'</span>';
            html+='<span class="dgt-cat-count">'+tools.length+'</span>';
            html+='<svg class="dgt-cat-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>';
            html+='</div>';
            html+='<div class="dgt-card-grid">';
            tools.forEach(function(tool,i){
                var delay=(catDelay*0.05)+(i*0.03);
                html+='<article class="dgt-card" id="card-'+tool.id+'" onclick="DGT.open(\''+tool.id+'\')" role="button" tabindex="0" style="animation-delay:'+delay+'s">';
                if(tool.preview)html+='<span class="dgt-card-preview-badge">Preview</span>';
                html+='<div class="dgt-card-top"><span class="dgt-card-icon">'+toolIcon(tool.id)+'</span><span class="dgt-card-title">'+tool.title+'</span></div>';
                html+='<p class="dgt-card-desc">'+tool.desc+'</p>';
                html+='<div class="dgt-card-footer">';
                html+='<div class="dgt-card-tags">';
                (tool.tags||[]).slice(0,2).forEach(function(tag,ti){html+='<span class="dgt-tag dgt-tag-'+tag+'" style="animation-delay:'+((delay)+(ti*0.05))+'s">'+tag+'</span>';});
                html+='</div>';
                html+='<span class="dgt-card-open">Open <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></span>';
                html+='</div></article>';
            });
            html+='</div></section>';
            catDelay++;
        });
        container.innerHTML=html;
    }

    /* ── Toggle category ─────────────────────────────────────────── */
    function toggleCat(id) {
        var el=document.getElementById(id);
        if(el)el.classList.toggle('collapsed');
    }

    /* ── Search ──────────────────────────────────────────────────── */
    function search(q) {
        q=q.toLowerCase().trim();
        var visible=0;
        TOOLS.forEach(function(t){
            var card=document.getElementById('card-'+t.id);
            if(!card)return;
            var match=!q||t.title.toLowerCase().includes(q)||t.desc.toLowerCase().includes(q)||(t.tags||[]).some(tag=>tag.includes(q))||t.cat.includes(q);
            card.style.display=match?'':'none';
            if(match)visible++;
        });
        document.querySelectorAll('.dgt-category').forEach(function(sec){
            var anyVisible=Array.from(sec.querySelectorAll('.dgt-card')).some(c=>c.style.display!=='none');
            sec.classList.toggle('hidden',!anyVisible);
            if(q&&anyVisible)sec.classList.remove('collapsed');
        });
        var nr=document.getElementById('dgt-no-results');
        if(nr)nr.classList.toggle('show',visible===0&&q!=='');
        var cnt=document.getElementById('dgt-search-count');
        if(cnt)cnt.textContent=q?(visible+' found'):'';
    }

    /* ── Build config form ───────────────────────────────────────── */
    function buildForm(tool) {
        var html='';
        (tool.inputs||[]).forEach(function(inp){
            if(inp.type==='checkbox'){
                html+='<div class="dgt-field"><label class="dgt-check-item"><input type="checkbox" id="dgt-inp-'+inp.id+'"'+(inp.checked?' checked':'')+'>'+inp.label+'</label></div>';
                return;
            }
            html+='<div class="dgt-field"><label for="dgt-inp-'+inp.id+'">'+inp.label+'</label>';
            if(inp.type==='select'){
                html+='<select class="dgt-select" id="dgt-inp-'+inp.id+'">';
                (inp.options||[]).forEach(function(o){var v=o.v||o,l=o.l||o;html+='<option value="'+v+'"'+(v===(inp.value||'')?' selected':'')+'>'+l+'</option>';});
                html+='</select>';
            } else if(inp.type==='toggle'){
                html+='<div class="dgt-toggle-group">';
                (inp.options||[]).forEach(function(o){html+='<button type="button" class="dgt-toggle-btn'+(o===(inp.value||'')?' active':'')+'" onclick="DGT.setToggle(\''+inp.id+'\',this,\''+o+'\')">'+o+'</button>';});
                html+='<input type="hidden" id="dgt-inp-'+inp.id+'" value="'+(inp.value||'')+'">';
                html+='</div>';
            } else if(inp.type==='slider'){
                html+='<div class="dgt-slider-wrap"><input type="range" class="dgt-slider" id="dgt-inp-'+inp.id+'" min="'+(inp.min||1)+'" max="'+(inp.max||100)+'" value="'+(inp.value||50)+'" oninput="document.getElementById(\'dgt-slv-'+inp.id+'\').textContent=this.value"><span class="dgt-slider-val" id="dgt-slv-'+inp.id+'">'+(inp.value||50)+'</span>'+(inp.unit?'<span style="font-size:11px;color:var(--color-text-subtle);margin-left:2px">'+inp.unit+'</span>':'')+'</div>';
            } else if(inp.type==='text'){
                html+='<input type="text" class="dgt-input" id="dgt-inp-'+inp.id+'" value="'+(inp.value||'')+'" placeholder="'+(inp.placeholder||'')+'">';
            } else {
                html+='<input type="number" class="dgt-input" id="dgt-inp-'+inp.id+'" value="'+(inp.value||1)+'" min="'+(inp.min||1)+'" max="'+(inp.max||1000)+'" style="max-width:120px">';
            }
            if(inp.help)html+='<span class="dgt-field-help">'+inp.help+'</span>';
            html+='</div>';
        });
        return html;
    }

    function readConfig(tool) {
        var cfg={};
        (tool.inputs||[]).forEach(function(inp){
            var el=document.getElementById('dgt-inp-'+inp.id);
            if(!el)return;
            if(inp.type==='checkbox')cfg[inp.id]=el.checked;
            else cfg[inp.id]=el.value;
        });
        return cfg;
    }

    function setToggle(inputId,btn,val) {
        var wrap=btn.closest('.dgt-toggle-group');
        if(wrap)wrap.querySelectorAll('.dgt-toggle-btn').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
        var hidden=document.getElementById('dgt-inp-'+inputId);
        if(hidden)hidden.value=val;
    }

    /* ── Open workspace ──────────────────────────────────────────── */
    function open(id) {
        var tool=TOOLS.find(t=>t.id===id);
        if(!tool)return;
        activeTool=tool;
        activeTab='output';

        document.querySelectorAll('.dgt-card').forEach(c=>c.classList.remove('active'));
        var card=document.getElementById('card-'+id);
        if(card)card.classList.add('active');

        var ws=document.getElementById('dgt-workspace');
        if(!ws)return;
        ws.classList.add('open');

        // Breadcrumb
        var catLabel=CATS[tool.cat]?CATS[tool.cat].label:tool.cat;
        var catId=CATS[tool.cat]?CATS[tool.cat].id:'';
        ws.querySelector('.dgt-ws-breadcrumb').innerHTML=
            '<a onclick="DGT.scrollToCat(\''+catId+'\')" style="color:var(--color-text-muted);text-decoration:none;cursor:pointer;transition:color .15s">'+catLabel+'</a>'
            +' <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg> '
            +'<span class="dgt-ws-breadcrumb-current">'+tool.title+'</span>';

        ws.querySelector('.dgt-config-title').textContent=tool.title;
        ws.querySelector('.dgt-config-desc').textContent=tool.desc;
        ws.querySelector('#dgt-ws-form').innerHTML=buildForm(tool);

        // Tab visibility
        var hasPreview=!!previewRenderers[tool.id];
        var previewTab=document.getElementById('dgt-tab-preview');
        if(previewTab)previewTab.style.display=hasPreview?'flex':'none';

        // Reset to output tab
        switchTab('output');

        // File extension hint
        var ext=id.includes('json')?'output.json':id.includes('csv')?'output.csv':id.includes('xml')?'output.xml':id.includes('sql')?'output.sql':id.includes('html')?'output.html':id.includes('css')?'output.css':id.includes('js')?'snippet.js':'output.txt';
        var fn=ws.querySelector('.dgt-code-filename');
        if(fn)fn.textContent=ext;

        setOutput('');

        // Clear preview
        var pb=document.getElementById('dgt-preview-body');
        if(pb)pb.innerHTML='<div class="dgt-preview-empty"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg><p>Click Generate to see preview</p></div>';

        // Related tools
        var relList=ws.querySelector('.dgt-related-list');
        if(relList){
            var rel=(tool.related||[]).map(function(rid){
                var rt=TOOLS.find(t=>t.id===rid);
                return rt?'<div class="dgt-related-item" onclick="DGT.open(\''+rid+'\')" role="button">'+toolIcon(rid)+'<span>'+rt.title+'</span></div>':'';
            }).join('');
            relList.innerHTML=rel||'<p style="font-size:11px;color:var(--color-text-subtle)">No related tools</p>';
        }

        setTimeout(()=>ws.scrollIntoView({behavior:'smooth',block:'start'}),80);
        setTimeout(function(){if(_hooks.onOpen)_hooks.onOpen(activeTool);},120);
    }

    /* ── Generate ────────────────────────────────────────────────── */
    function generate() {
        if(!activeTool)return;
        var btn=document.getElementById('dgt-gen-btn');
        if(btn){btn.disabled=true;btn.innerHTML='<span class="dgt-spinner"></span> Generating…';}
        setTimeout(function(){
            var result='';
            try{
                var cfg=readConfig(activeTool);
                result=activeTool.fn(cfg)||'(empty result)';
                setOutput(result);
                if(_hooks.onGenerate)_hooks.onGenerate(activeTool,result);
                toast('✓ Generated!');
                // Auto-update preview if on preview tab
                if(activeTab==='preview'){
                    renderPreview(activeTool.id,result,cfg);
                }
            }catch(e){
                setOutput('Error: '+e.message);
                toast('Generation failed');
            }
            if(btn){btn.disabled=false;btn.innerHTML='<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg> Generate';}
        },10);
    }

    /* ── Close workspace ─────────────────────────────────────────── */
    function closeWorkspace() {
        var ws=document.getElementById('dgt-workspace');
        if(ws)ws.classList.remove('open');
        document.querySelectorAll('.dgt-card').forEach(c=>c.classList.remove('active'));
        activeTool=null;
    }

    function scrollToCat(id) {
        var el=document.getElementById(id);
        if(el){el.scrollIntoView({behavior:'smooth',block:'start'});el.classList.remove('collapsed');}
    }

    /* ── Keyboard ────────────────────────────────────────────────── */
    function initKeyboard() {
        document.addEventListener('keydown',function(e){
            if((e.ctrlKey||e.metaKey)&&e.key==='k'){e.preventDefault();var s=document.getElementById('dgt-search');if(s)s.focus();}
            if(e.key==='Escape'){closeWorkspace();var s=document.getElementById('dgt-search');if(s&&document.activeElement===s){s.value='';search('');s.blur();}}
        });
        // Enter on card
        document.addEventListener('keypress',function(e){if(e.key==='Enter'&&document.activeElement&&document.activeElement.classList.contains('dgt-card'))document.activeElement.click();});
    }

    function init() { renderGrid(); initKeyboard(); }

    /* ── PUBLIC API ──────────────────────────────────────────────── */
    return {
        init, open, generate, search, closeWorkspace, toggleCat, scrollToCat, setToggle, liveRegex, switchTab,
        copy: function(text) {
            if(typeof text==='string'&&text){copyText(text);return;}
            var ta=document.getElementById('dgt-ws-output');
            if(ta)copyText(ta.value);
        },
        download: function() {
            var ta=document.getElementById('dgt-ws-output');
            var fn=document.querySelector('.dgt-code-filename');
            if(ta&&ta.value)download(ta.value,(fn&&fn.textContent)||'output.txt');
        },
        clear: function() { setOutput(''); },
        regenerate: function() { generate(); },
        // Internal surface for dgt-features.js
        _state:   function() { return { activeTool:activeTool, activeTab:activeTab }; },
        _tools:   TOOLS,
        _cats:    CATS,
        _hooks:   _hooks,
        _toolIcon:toolIcon,
        _toast:   toast,
        _setOutput: setOutput,
    };
})();

document.addEventListener('DOMContentLoaded', DGT.init);
