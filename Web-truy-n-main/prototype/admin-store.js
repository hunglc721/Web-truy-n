/**
 * admin-store.js — Tầng dữ liệu chung cho tất cả trang Admin
 * WebComics Admin Panel
 */

// ─── UTILITIES ────────────────────────────────────────────────
function escapeHtml(str) {
  if (str == null) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function generateId() {
  return Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
}

function generateSlug(str) {
  const map = {
    'à':'a','á':'a','ả':'a','ã':'a','ạ':'a','ă':'a','ắ':'a','ặ':'a','ằ':'a','ẳ':'a','ẵ':'a',
    'â':'a','ấ':'a','ậ':'a','ầ':'a','ẩ':'a','ẫ':'a','è':'e','é':'e','ẹ':'e','ẻ':'e','ẽ':'e',
    'ê':'e','ế':'e','ệ':'e','ề':'e','ể':'e','ễ':'e','ì':'i','í':'i','ị':'i','ỉ':'i','ĩ':'i',
    'ò':'o','ó':'o','ọ':'o','ỏ':'o','õ':'o','ô':'o','ố':'o','ộ':'o','ồ':'o','ổ':'o','ỗ':'o',
    'ơ':'o','ớ':'o','ợ':'o','ờ':'o','ở':'o','ỡ':'o','ù':'u','ú':'u','ụ':'u','ủ':'u','ũ':'u',
    'ư':'u','ứ':'u','ự':'u','ừ':'u','ử':'u','ữ':'u','ỳ':'y','ý':'y','ỵ':'y','ỷ':'y','ỹ':'y',
    'đ':'d','À':'a','Á':'a','Ả':'a','Ã':'a','Ạ':'a','Ă':'a','Ắ':'a','Ặ':'a','Ằ':'a','Ẳ':'a',
    'Ẵ':'a','Â':'a','Ấ':'a','Ậ':'a','Ầ':'a','Ẩ':'a','Ẫ':'a','È':'e','É':'e','Ẹ':'e','Ẻ':'e',
    'Ẽ':'e','Ê':'e','Ế':'e','Ệ':'e','Ề':'e','Ể':'e','Ễ':'e','Ì':'i','Í':'i','Ị':'i','Ỉ':'i',
    'Ĩ':'i','Ò':'o','Ó':'o','Ọ':'o','Ỏ':'o','Õ':'o','Ô':'o','Ố':'o','Ộ':'o','Ồ':'o','Ổ':'o',
    'Ỗ':'o','Ơ':'o','Ớ':'o','Ợ':'o','Ờ':'o','Ở':'o','Ỡ':'o','Ù':'u','Ú':'u','Ụ':'u','Ủ':'u',
    'Ũ':'u','Ư':'u','Ứ':'u','Ự':'u','Ừ':'u','Ử':'u','Ữ':'u','Ỳ':'y','Ý':'y','Ỵ':'y','Ỷ':'y',
    'Ỹ':'y','Đ':'d'
  };
  return str.split('').map(c => map[c] || c).join('')
    .toLowerCase().replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-').replace(/-+/g, '-');
}

function formatDate(ts) {
  if (!ts) return '—';
  const d = new Date(ts);
  return d.toLocaleDateString('vi-VN', { day:'2-digit', month:'2-digit', year:'numeric' }) +
         ' ' + d.toLocaleTimeString('vi-VN', { hour:'2-digit', minute:'2-digit' });
}

function timeAgo(ts) {
  const diff = Date.now() - ts;
  const m = Math.floor(diff / 60000);
  if (m < 1) return 'Vừa xong';
  if (m < 60) return `${m} phút trước`;
  const h = Math.floor(m / 60);
  if (h < 24) return `${h} giờ trước`;
  const day = Math.floor(h / 24);
  if (day < 30) return `${day} ngày trước`;
  return formatDate(ts);
}

// ─── TOAST NOTIFICATIONS ──────────────────────────────────────
(function initToast() {
  if (document.getElementById('wc-toast-container')) return;
  const container = document.createElement('div');
  container.id = 'wc-toast-container';
  container.style.cssText = `
    position:fixed; bottom:24px; right:24px; z-index:99999;
    display:flex; flex-direction:column-reverse; gap:10px; pointer-events:none;
  `;
  document.body.appendChild(container);
})();

function toast(msg, type, duration) {
  type = type || 'success';
  duration = duration || 3500;
  const container = document.getElementById('wc-toast-container');
  if (!container) return;
  const colors = {
    success: { bg: 'rgba(34,197,94,.15)', border: 'rgba(34,197,94,.35)', text: '#4ade80', icon: '✓' },
    error:   { bg: 'rgba(239,68,68,.15)', border: 'rgba(239,68,68,.35)', text: '#f87171', icon: '✕' },
    info:    { bg: 'rgba(59,130,246,.15)', border: 'rgba(59,130,246,.35)', text: '#60a5fa', icon: 'ℹ' },
    warning: { bg: 'rgba(245,158,11,.15)', border: 'rgba(245,158,11,.35)', text: '#fbbf24', icon: '⚠' },
  };
  const c = colors[type] || colors.success;
  const el = document.createElement('div');
  el.style.cssText = `background:${c.bg};border:1px solid ${c.border};color:${c.text};padding:12px 16px;border-radius:10px;font-size:13.5px;font-weight:600;display:flex;align-items:center;gap:10px;pointer-events:all;backdrop-filter:blur(12px);box-shadow:0 4px 20px rgba(0,0,0,.4);max-width:360px;animation:wc-slide-in .25s ease;opacity:1;transition:opacity .3s;`;
  el.innerHTML = `<span style="font-size:16px">${c.icon}</span><span>${escapeHtml(msg)}</span>`;
  if (!document.getElementById('wc-toast-style')) {
    const s = document.createElement('style');
    s.id = 'wc-toast-style';
    s.textContent = '@keyframes wc-slide-in{from{transform:translateX(110%);opacity:0}to{transform:translateX(0);opacity:1}}';
    document.head.appendChild(s);
  }
  container.appendChild(el);
  setTimeout(function() { el.style.opacity = '0'; setTimeout(function() { el.remove(); }, 300); }, duration);
}

// ─── AUTH ──────────────────────────────────────────────────────
const Auth = {
  SESSION_KEY: 'wc_admin_session',

  getSession: function() {
    try { return JSON.parse(localStorage.getItem(this.SESSION_KEY) || 'null'); }
    catch(e) { return null; }
  },

  setSession: function(user) {
    localStorage.setItem(this.SESSION_KEY, JSON.stringify(Object.assign({}, user, { loginAt: Date.now() })));
  },

  clearSession: function() {
    localStorage.removeItem(this.SESSION_KEY);
  },

  isLoggedIn: function() {
    const s = this.getSession();
    return !!(s && s.name);
  },

  guard: function(redirectTo) {
    redirectTo = redirectTo || 'admin-login.html';
    if (!this.isLoggedIn()) {
      window.location.href = redirectTo;
      return false;
    }
    return true;
  },

  syncSidebar: function() {
    const s = this.getSession();
    if (!s) return;
    const av = document.getElementById('sidebar-av');
    const nm = document.getElementById('sidebar-name');
    const rl = document.getElementById('sidebar-role');
    if (av) av.textContent = (s.name || 'A')[0].toUpperCase();
    if (nm) nm.textContent = s.name || 'Admin';
    if (rl) rl.textContent = '⚡ ' + (s.role === 'admin' ? 'Administrator' : s.role === 'moderator' ? 'Moderator' : 'Editor');
  }
};

// ─── STORAGE HELPERS ──────────────────────────────────────────
function storeGet(key, fallback) {
  if (fallback === undefined) fallback = [];
  try { return JSON.parse(localStorage.getItem(key) || JSON.stringify(fallback)); }
  catch(e) { return fallback; }
}

function storeSet(key, value) {
  localStorage.setItem(key, JSON.stringify(value));
}

// ─── ADMIN STORE ──────────────────────────────────────────────
var AdminStore = {

  // ── COMICS ──────────────────────────────────────────────────
  comics: {
    KEY: 'admin_comics',
    SEED_DONE: 'admin_comics_seeded',

    _seed: function() {
      if (localStorage.getItem(this.SEED_DONE)) return;
      var seed = [
        { id:'c1', title:'Solo Leveling', slug:'solo-leveling', cover:'https://upload.wikimedia.org/wikipedia/en/6/6c/Solo_Leveling_Volume_1_Cover.jpg', authorId:'', genres:['Action','Fantasy'], tags:['Manhwa','SYSTEM'], status:'ONGOING', type:'Manhwa', year:2018, desc:'Câu chuyện về Sung Jin-Woo, thợ săn yếu nhất thế giới, trở thành thợ săn mạnh nhất sau khi nhận được hệ thống đặc biệt.', chapterCount:200, views:15800000, createdAt:Date.now()-86400000*30 },
        { id:'c2', title:'Tower of God', slug:'tower-of-god', cover:'https://upload.wikimedia.org/wikipedia/en/7/7d/Tower_of_God_Volume_1_Cover.jpg', authorId:'', genres:['Fantasy','Mystery'], tags:['Manhwa'], status:'ONGOING', type:'Manhwa', year:2010, desc:'Bam leo lên ngọn tháp để tìm người bạn thân Rachel.', chapterCount:590, views:11200000, createdAt:Date.now()-86400000*60 },
        { id:'c3', title:"Omniscient Reader's Viewpoint", slug:'omniscient-readers-viewpoint', cover:"https://upload.wikimedia.org/wikipedia/en/6/69/Omniscient_Reader%27s_Viewpoint_Volume_1_Cover.jpg", authorId:'', genres:['Action','Fantasy'], tags:['Manhwa'], status:'ONGOING', type:'Manhwa', year:2020, desc:'Độc giả duy nhất của câu chuyện kết thúc thế giới đột nhiên sống vào trong đó.', chapterCount:185, views:9400000, createdAt:Date.now()-86400000*20 },
        { id:'c4', title:'Jujutsu Kaisen', slug:'jujutsu-kaisen', cover:'https://upload.wikimedia.org/wikipedia/en/4/46/Jujutsu_kaisen.jpg', authorId:'', genres:['Action','Supernatural'], tags:['Manga','Shonen'], status:'ONGOING', type:'Manga', year:2018, desc:'Yuji Itadori nuốt ngón tay của Ryomen Sukuna và trở thành vật chứa của lời nguyền mạnh nhất.', chapterCount:254, views:6800000, createdAt:Date.now()-86400000*90 },
        { id:'c5', title:'Spy × Family', slug:'spy-family', cover:'https://upload.wikimedia.org/wikipedia/en/5/51/Spy_Family_vol_1.jpg', authorId:'', genres:['Comedy','Action'], tags:['Manga'], status:'ONGOING', type:'Manga', year:2019, desc:'Điệp viên Loid phải lập một gia đình giả để hoàn thành nhiệm vụ đặc biệt.', chapterCount:96, views:5200000, createdAt:Date.now()-86400000*45 },
        { id:'c6', title:'Chainsaw Man', slug:'chainsaw-man', cover:'https://upload.wikimedia.org/wikipedia/en/2/24/Chainsawman.jpg', authorId:'', genres:['Action','Horror'], tags:['Manga','Dark'], status:'ONGOING', type:'Manga', year:2018, desc:'Denji, một thợ săn quỷ nghèo khổ, hợp nhất với con quỷ cưa của mình và trở thành Chainsaw Man.', chapterCount:160, views:4800000, createdAt:Date.now()-86400000*80 },
      ];
      storeSet(this.KEY, seed);
      localStorage.setItem(this.SEED_DONE, '1');
    },

    getAll: function() { this._seed(); return storeGet(this.KEY, []); },

    getById: function(id) { return this.getAll().find(function(c) { return c.id === id; }) || null; },

    slugExists: function(slug, excludeId) {
      excludeId = excludeId || null;
      return this.getAll().some(function(c) { return c.slug === slug && c.id !== excludeId; });
    },

    save: function(data) {
      var list = this.getAll();
      var session = Auth.getSession();
      var actor = session ? session.name : 'Unknown';
      if (data.id) {
        var idx = list.findIndex(function(c) { return c.id === data.id; });
        if (idx >= 0) {
          list[idx] = Object.assign({}, list[idx], data, { updatedAt: Date.now() });
          storeSet(this.KEY, list);
          AdminStore.audit.log('UPDATE', 'Comics', data.title || data.id, actor);
          return list[idx];
        }
      }
      var item = Object.assign({}, data, { id: generateId(), createdAt: Date.now(), chapterCount: 0, views: 0 });
      list.unshift(item);
      storeSet(this.KEY, list);
      AdminStore.audit.log('CREATE', 'Comics', item.title, actor);
      return item;
    },

    delete: function(id) {
      var list = this.getAll();
      var item = list.find(function(c) { return c.id === id; });
      var filtered = list.filter(function(c) { return c.id !== id; });
      storeSet(this.KEY, filtered);
      var chapters = storeGet(AdminStore.chapters.KEY, []).filter(function(ch) { return ch.comicId !== id; });
      storeSet(AdminStore.chapters.KEY, chapters);
      var session = Auth.getSession();
      AdminStore.audit.log('DELETE', 'Comics', item ? item.title : id, session ? session.name : 'Unknown');
    },

    updateChapterCount: function(comicId) {
      var list = this.getAll();
      var idx = list.findIndex(function(c) { return c.id === comicId; });
      if (idx < 0) return;
      var count = AdminStore.chapters.getByComic(comicId).length;
      list[idx].chapterCount = count;
      storeSet(this.KEY, list);
    }
  },

  // ── CHAPTERS ────────────────────────────────────────────────
  chapters: {
    KEY: 'admin_chapters',

    getAll: function() { return storeGet(this.KEY, []); },

    getByComic: function(comicId) {
      return this.getAll().filter(function(ch) { return ch.comicId === comicId; })
        .sort(function(a, b) { return (a.order || 0) - (b.order || 0); });
    },

    numberExists: function(comicId, number, excludeId) {
      excludeId = excludeId || null;
      return this.getAll().some(function(ch) {
        return ch.comicId === comicId && ch.number === number && ch.id !== excludeId;
      });
    },

    save: function(data) {
      var list = this.getAll();
      var session = Auth.getSession();
      var actor = session ? session.name : 'Unknown';
      var comic = AdminStore.comics.getById(data.comicId);
      var label = 'Ch.' + data.number + (comic ? ' — ' + comic.title : '');
      if (data.id) {
        var idx = list.findIndex(function(ch) { return ch.id === data.id; });
        if (idx >= 0) {
          list[idx] = Object.assign({}, list[idx], data, { updatedAt: Date.now() });
          storeSet(this.KEY, list);
          AdminStore.audit.log('UPDATE', 'Chapters', label, actor);
          AdminStore.comics.updateChapterCount(data.comicId);
          return list[idx];
        }
      }
      var comicChaps = list.filter(function(ch) { return ch.comicId === data.comicId; });
      var maxOrder = comicChaps.length ? Math.max.apply(null, comicChaps.map(function(ch) { return ch.order || 0; })) : 0;
      var item = Object.assign({}, data, { id: generateId(), order: maxOrder + 1, views: 0, createdAt: Date.now() });
      list.push(item);
      storeSet(this.KEY, list);
      AdminStore.audit.log('CREATE', 'Chapters', label, actor);
      AdminStore.comics.updateChapterCount(data.comicId);
      return item;
    },

    delete: function(id) {
      var list = this.getAll();
      var item = list.find(function(ch) { return ch.id === id; });
      var filtered = list.filter(function(ch) { return ch.id !== id; });
      storeSet(this.KEY, filtered);
      if (item) AdminStore.comics.updateChapterCount(item.comicId);
      var session = Auth.getSession();
      AdminStore.audit.log('DELETE', 'Chapters', item ? 'Ch.' + item.number : id, session ? session.name : 'Unknown');
    },

    reorder: function(comicId, orderedIds) {
      var list = this.getAll();
      orderedIds.forEach(function(id, idx) {
        var i = list.findIndex(function(ch) { return ch.id === id; });
        if (i >= 0) list[i].order = idx + 1;
      });
      storeSet(this.KEY, list);
    },

    nextNumber: function(comicId) {
      var chaps = this.getByComic(comicId);
      if (!chaps.length) return 1;
      return Math.max.apply(null, chaps.map(function(ch) { return ch.number || 0; })) + 1;
    }
  },

  // ── AUDIT LOG ───────────────────────────────────────────────
  audit: {
    KEY: 'admin_audit_log',
    MAX: 500,

    log: function(action, module, target, actor) {
      actor = actor || 'System';
      var list = storeGet(this.KEY, []);
      list.unshift({
        id: generateId(),
        action: action,
        module: module,
        target: target,
        actor: actor,
        ip: '127.0.0.1',
        ts: Date.now()
      });
      storeSet(this.KEY, list.slice(0, this.MAX));
    },

    getAll: function() { return storeGet(this.KEY, []); },

    clear: function() { storeSet(this.KEY, []); }
  },

  // ── BANNERS ─────────────────────────────────────────────────
  banners: {
    KEY: 'admin_banners',

    _seed: function() {
      if (localStorage.getItem('admin_banners_seeded')) return;
      storeSet(this.KEY, [
        { id:'b1', title:'Solo Leveling Season 2', imageUrl:'https://upload.wikimedia.org/wikipedia/en/6/6c/Solo_Leveling_Volume_1_Cover.jpg', linkUrl:'#', active:true, order:1, startAt:'', endAt:'', clicks:1240, createdAt:Date.now() },
        { id:'b2', title:'Tower of God Campaign', imageUrl:'https://upload.wikimedia.org/wikipedia/en/7/7d/Tower_of_God_Volume_1_Cover.jpg', linkUrl:'#', active:true, order:2, startAt:'', endAt:'', clicks:870, createdAt:Date.now() },
        { id:'b3', title:'Omniscient Promo', imageUrl:"https://upload.wikimedia.org/wikipedia/en/6/69/Omniscient_Reader%27s_Viewpoint_Volume_1_Cover.jpg", linkUrl:'#', active:false, order:3, startAt:'', endAt:'', clicks:345, createdAt:Date.now() },
      ]);
      localStorage.setItem('admin_banners_seeded', '1');
    },

    getAll: function() { this._seed(); return storeGet(this.KEY, []); },

    save: function(data) {
      var list = this.getAll();
      var session = Auth.getSession();
      var actor = session ? session.name : 'Unknown';
      if (data.id) {
        var idx = list.findIndex(function(b) { return b.id === data.id; });
        if (idx >= 0) {
          list[idx] = Object.assign({}, list[idx], data);
          storeSet(this.KEY, list);
          AdminStore.audit.log('UPDATE', 'Banners', data.title || data.id, actor);
          return list[idx];
        }
      }
      var maxOrder = list.length ? Math.max.apply(null, list.map(function(b) { return b.order || 0; })) : 0;
      var item = Object.assign({}, data, { id: generateId(), order: maxOrder + 1, clicks: 0, createdAt: Date.now() });
      list.push(item);
      storeSet(this.KEY, list);
      AdminStore.audit.log('CREATE', 'Banners', item.title, actor);
      return item;
    },

    delete: function(id) {
      var list = this.getAll();
      var item = list.find(function(b) { return b.id === id; });
      storeSet(this.KEY, list.filter(function(b) { return b.id !== id; }));
      var session = Auth.getSession();
      AdminStore.audit.log('DELETE', 'Banners', item ? item.title : id, session ? session.name : 'Unknown');
    }
  },

  // ── SCHEDULES ───────────────────────────────────────────────
  schedules: {
    KEY: 'admin_schedules_v2',

    _seed: function() {
      if (localStorage.getItem('admin_schedules_v2_seeded')) return;
      var days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
      var comics = AdminStore.comics.getAll();
      var entries = [];
      comics.slice(0, 6).forEach(function(c, i) {
        entries.push({ id: generateId(), comicId: c.id, comicTitle: c.title, comicCover: c.cover, day: days[i % 7], time:'20:00', note:'', createdAt: Date.now() });
      });
      storeSet(this.KEY, entries);
      localStorage.setItem('admin_schedules_v2_seeded', '1');
    },

    getAll: function() { this._seed(); return storeGet(this.KEY, []); },

    getByDay: function(day) { return this.getAll().filter(function(s) { return s.day === day; }); },

    save: function(data) {
      var list = this.getAll();
      if (data.id) {
        var idx = list.findIndex(function(s) { return s.id === data.id; });
        if (idx >= 0) { list[idx] = Object.assign({}, list[idx], data); storeSet(this.KEY, list); return list[idx]; }
      }
      var item = Object.assign({}, data, { id: generateId(), createdAt: Date.now() });
      list.push(item);
      storeSet(this.KEY, list);
      return item;
    },

    delete: function(id) { storeSet(this.KEY, this.getAll().filter(function(s) { return s.id !== id; })); }
  },

  // ── COMMENTS ────────────────────────────────────────────────
  comments: {
    KEY: 'admin_comments_v2',

    _seed: function() {
      if (localStorage.getItem('admin_comments_seeded')) return;
      var comics = AdminStore.comics.getAll();
      var users = ['NguyenA_Fan','MinhKhoi_Read','ThanhTruc123','AnhDang_Pro','LinhTran_Otaku','HoangDuc_MK','TuanAnh_TBK','PhuongNga_YY','DucLinh_Weeb','KhanhHoa_Reader'];
      var texts = [
        'Truyện hay quá tác giả ơi!','Chapter này đỉnh thật sự!','Bao giờ ra chapter mới?',
        'Nét vẽ đẹp xỉu, tình tiết hấp dẫn.','Cốt truyện sâu sắc quá!','Mình đợi bộ này hoài!',
        'Tác giả thiên tài!','Xúc động đến phát khóc luôn.','Hype quá đi, ra nhanh nhanh!',
        'Plot twist không ai ngờ đến.','Art style đẹp như tranh sơn dầu vậy.','Chap này hay nhất từ trước đến nay!'
      ];
      var seed = [];
      for (var i = 0; i < 60; i++) {
        var c = comics[i % comics.length] || { id:'c1', title:'Solo Leveling', slug:'solo-leveling' };
        seed.push({
          id: generateId(),
          user: users[i % users.length],
          comicId: c.id,
          comicTitle: c.title,
          chapter: 'Chapter ' + (Math.floor(Math.random() * 200) + 1),
          text: texts[i % texts.length],
          status: i % 5 === 0 ? 'pending' : 'approved',
          locked: false,
          ts: Date.now() - Math.random() * 86400000 * 30
        });
      }
      storeSet(this.KEY, seed);
      localStorage.setItem('admin_comments_seeded', '1');
    },

    getAll: function() { this._seed(); return storeGet(this.KEY, []); },

    update: function(id, data) {
      var list = this.getAll();
      var idx = list.findIndex(function(c) { return c.id === id; });
      if (idx >= 0) { list[idx] = Object.assign({}, list[idx], data); storeSet(this.KEY, list); }
    },

    delete: function(id) { storeSet(this.KEY, this.getAll().filter(function(c) { return c.id !== id; })); },

    bulkDelete: function(ids) { storeSet(this.KEY, this.getAll().filter(function(c) { return ids.indexOf(c.id) < 0; })); },

    bulkApprove: function(ids) {
      var list = this.getAll();
      list.forEach(function(c) { if (ids.indexOf(c.id) >= 0) c.status = 'approved'; });
      storeSet(this.KEY, list);
    }
  },

  // ── REPORTS ─────────────────────────────────────────────────
  reports: {
    KEY: 'admin_reports_v2',

    _seed: function() {
      if (localStorage.getItem('admin_reports_seeded')) return;
      var comics = AdminStore.comics.getAll();
      var types = ['Ảnh lỗi/vỡ','Ảnh thiếu trang','Chương sai thứ tự','Dịch sai nghĩa','Ảnh mờ/chất lượng thấp','Sai số chapter'];
      var seed = [];
      for (var i = 0; i < 25; i++) {
        var c = comics[i % comics.length] || { id:'c1', title:'Solo Leveling' };
        seed.push({
          id: generateId(),
          comicId: c.id,
          comicTitle: c.title,
          chapter: 'Chapter ' + (Math.floor(Math.random() * 200) + 1),
          type: types[i % types.length],
          note: '',
          assignee: '',
          status: ['pending','processing','resolved'][i % 3],
          reporterUser: 'User' + (i + 1),
          ts: Date.now() - Math.random() * 86400000 * 14
        });
      }
      storeSet(this.KEY, seed);
      localStorage.setItem('admin_reports_seeded', '1');
    },

    getAll: function() { this._seed(); return storeGet(this.KEY, []); },

    update: function(id, data) {
      var list = this.getAll();
      var idx = list.findIndex(function(r) { return r.id === id; });
      if (idx >= 0) { list[idx] = Object.assign({}, list[idx], data, { updatedAt: Date.now() }); storeSet(this.KEY, list); }
    }
  },

  // ── PERMISSIONS ─────────────────────────────────────────────
  permissions: {
    KEY: 'admin_permissions_v2',

    DEFAULT: {
      admin:     { comics:true,  chapters:true,  genres:true,  tags:true,  authors:true,  users:true,  comments:true,  reports:true,  banners:true,  settings:true  },
      moderator: { comics:false, chapters:true,  genres:false, tags:false, authors:false, users:false, comments:true,  reports:true,  banners:false, settings:false },
      editor:    { comics:true,  chapters:true,  genres:true,  tags:true,  authors:true,  users:false, comments:false, reports:false, banners:false, settings:false },
      viewer:    { comics:false, chapters:false, genres:false, tags:false, authors:false, users:false, comments:false, reports:false, banners:false, settings:false },
    },

    get: function() {
      var stored = storeGet(this.KEY, null);
      return stored || JSON.parse(JSON.stringify(this.DEFAULT));
    },

    save: function(data) {
      storeSet(this.KEY, data);
      var session = Auth.getSession();
      AdminStore.audit.log('PERMISSION', 'Permissions', 'Ma trận phân quyền', session ? session.name : 'Unknown');
    }
  },

  // ── SETTINGS ────────────────────────────────────────────────
  settings: {
    KEY: 'admin_settings_v2',

    DEFAULT: {
      siteName: 'WebComics - Đọc Truyện Tranh Online Chiều Sâu',
      tagline: 'Kho truyện tranh Manhwa, Manga, Comic hàng đầu Việt Nam',
      metaDesc: 'WebComics là nền tảng đọc truyện tranh miễn phí chất lượng cao, cập nhật chapter mới liên tục 24/7.',
      keywords: 'doc truyen tranh, solo leveling, manhwa, manga, webcomics',
      logoUrl: '', faviconUrl: '', ogImageUrl: '', gaId: '',
      fbUrl: '', twitterUrl: '', discordUrl: '',
      maintenanceMode: false,
      maintenanceMsg: 'Hệ thống đang bảo trì, vui lòng quay lại sau.',
      maintenanceIps: '127.0.0.1'
    },

    get: function() {
      return Object.assign({}, this.DEFAULT, storeGet(this.KEY, {}));
    },

    save: function(data) {
      storeSet(this.KEY, data);
      var session = Auth.getSession();
      AdminStore.audit.log('UPDATE', 'Settings', 'Cài đặt Website', session ? session.name : 'Unknown');
    }
  }
};

// ─── MODAL HELPERS ────────────────────────────────────────────
function openModal(id) {
  var el = document.getElementById(id);
  if (el) { el.classList.add('show'); }
}

function closeModal(id) {
  var el = document.getElementById(id);
  if (el) el.classList.remove('show');
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.show').forEach(function(m) { m.classList.remove('show'); });
  }
});

document.addEventListener('click', function(e) {
  if (e.target && e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('show');
  }
});

// ─── SIDEBAR BUILDER ──────────────────────────────────────────
function buildAdminSidebar(activePage) {
  activePage = activePage || '';
  function navLink(page, href, icon, label) {
    var isActive = page === activePage ? ' active' : '';
    return '<a href="' + href + '" class="nav-link' + isActive + '">' + icon + escapeHtml(label) + '</a>';
  }
  var svgIcons = {
    dashboard: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
    comics: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>',
    chapters: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/><line x1="12" y1="6" x2="12" y2="14"/><line x1="8" y1="10" x2="16" y2="10"/></svg>',
    genres: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h7"/></svg>',
    tags: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
    authors: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    comments: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>',
    reports: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    schedules: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
    banners: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
    users: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>',
    permissions: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>',
    settings: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>',
    audit: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
    analytics: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
    preview: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>',
  };
  function ic(name) { return svgIcons[name] || ''; }
  var s = Auth.getSession() || {};
  return '<aside class="sidebar">' +
    '<a href="admin.html" class="brand">' +
      '<div class="brand-icon">WC</div>' +
      '<div><div class="brand-text">WebComics</div><div class="brand-sub">Admin Panel</div></div>' +
    '</a>' +
    '<nav class="nav">' +
      '<div class="nav-sec">TỔNG QUAN</div>' +
      navLink('dashboard','admin.html',ic('dashboard'),'Dashboard') +
      navLink('analytics','admin-analytics.html',ic('analytics'),'Thống kê & Báo cáo') +

      '<div class="nav-sec">NỘI DUNG</div>' +
      navLink('comics','admin-comics.html',ic('comics'),'Quản lý Truyện') +
      navLink('chapters','admin-chapters.html',ic('chapters'),'Quản lý Chapter') +
      navLink('genres','admin-genres.html',ic('genres'),'Thể loại') +
      navLink('tags','admin-tags.html',ic('tags'),'Tags') +
      navLink('authors','admin-authors.html',ic('authors'),'Tác giả') +

      '<div class="nav-sec">TƯƠNG TÁC</div>' +
      navLink('comments','admin-comments.html',ic('comments'),'Kiểm duyệt Bình luận') +
      navLink('reports','admin-reports.html',ic('reports'),'Báo cáo Lỗi Chapter') +

      '<div class="nav-sec">VẬN HÀNH</div>' +
      navLink('schedules','admin-schedules.html',ic('schedules'),'Lịch ra truyện') +
      navLink('banners','admin-banners.html',ic('banners'),'Banner Quảng cáo') +

      '<div class="nav-sec">NGƯỜI DÙNG</div>' +
      navLink('users','admin-users.html',ic('users'),'Quản lý Thành viên') +
      navLink('permissions','admin-permissions.html',ic('permissions'),'Phân quyền') +

      '<div class="nav-sec">HỆ THỐNG</div>' +
      navLink('audit','admin-audit.html',ic('audit'),'Nhật ký Hoạt động') +
      navLink('settings','admin-settings.html',ic('settings'),'Cài đặt Website') +
      '<a href="index.html" target="_blank" class="nav-link">' + ic('preview') + 'Xem trang web</a>' +
    '</nav>' +
    '<div class="sidebar-footer">' +
      '<div class="user-pill">' +
        '<div class="user-av" id="sidebar-av">' + ((s.name||'A')[0].toUpperCase()) + '</div>' +
        '<div><div class="user-name" id="sidebar-name">' + escapeHtml(s.name||'Admin') + '</div>' +
        '<div class="user-role" id="sidebar-role">⚡ ' + (s.role==='admin'?'Administrator':s.role==='moderator'?'Moderator':'Editor') + '</div></div>' +
      '</div>' +
    '</div>' +
  '</aside>';
}

