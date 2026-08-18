const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');

const PORT = 3000;

// Dữ liệu Mock API
const trendingComics = [
  { id: 1, title: 'Solo Leveling', genre: 'Action · Fantasy', cover: 'https://upload.wikimedia.org/wikipedia/en/6/6c/Solo_Leveling_Volume_1_Cover.jpg', rank: 1, rankClass: 'r1' },
  { id: 2, title: 'Tower of God', genre: 'Fantasy · Mystery', cover: 'https://upload.wikimedia.org/wikipedia/en/7/7d/Tower_of_God_Volume_1_Cover.jpg', rank: 2, rankClass: 'r2' },
  { id: 3, title: "Omniscient Reader's Viewpoint", genre: 'Action · Fantasy', cover: 'https://upload.wikimedia.org/wikipedia/en/6/69/Omniscient_Reader%27s_Viewpoint_Volume_1_Cover.jpg', rank: 3, rankClass: 'r3' },
  { id: 4, title: 'Lore Olympus', genre: 'Romance · Mythology', cover: 'https://upload.wikimedia.org/wikipedia/en/7/72/Lore_Olympus_Banner_Art.png', rank: 4, rankClass: '' },
  { id: 5, title: 'Demon Slayer: Kimetsu no Yaiba', genre: 'Action · Supernatural', cover: 'https://upload.wikimedia.org/wikipedia/en/0/09/Demon_Slayer_-_Kimetsu_no_Yaiba%2C_volume_1.jpg', rank: 5, rankClass: '' },
  { id: 6, title: 'Jujutsu Kaisen', genre: 'Action · Fantasy', cover: 'https://upload.wikimedia.org/wikipedia/en/4/46/Jujutsu_kaisen.jpg', rank: 6, rankClass: '' },
  { id: 7, title: 'Spy × Family', genre: 'Comedy · Action', cover: 'https://upload.wikimedia.org/wikipedia/en/5/51/Spy_Family_vol_1.jpg', rank: 7, rankClass: '' },
  { id: 8, title: 'Chainsaw Man', genre: 'Action · Horror', cover: 'https://upload.wikimedia.org/wikipedia/en/2/24/Chainsawman.jpg', rank: 8, rankClass: '' },
  { id: 9, title: 'Attack on Titan', genre: 'Action · Drama', cover: 'https://upload.wikimedia.org/wikipedia/en/d/d6/Shingeki_no_Kyojin_manga_volume_1.jpg', rank: 9, rankClass: '' }
];

const latestComics = [
  { id: 1, title: 'Solo Leveling', genre: 'action', genreText: 'Action', timeAgo: '2h ago', chapter: 'Ch.200', badgeClass: 'hot-badge', cover: 'https://upload.wikimedia.org/wikipedia/en/6/6c/Solo_Leveling_Volume_1_Cover.jpg' },
  { id: 2, title: 'Tower of God', genre: 'fantasy', genreText: 'Fantasy', timeAgo: '3h ago', chapter: 'Ch.590', badgeClass: '', cover: 'https://upload.wikimedia.org/wikipedia/en/7/7d/Tower_of_God_Volume_1_Cover.jpg' },
  { id: 3, title: 'Omniscient Reader', genre: 'action', genreText: 'Action', timeAgo: '5h ago', chapter: 'Ch.185', badgeClass: 'hot-badge', cover: 'https://upload.wikimedia.org/wikipedia/en/6/69/Omniscient_Reader%27s_Viewpoint_Volume_1_Cover.jpg' },
  { id: 4, title: 'Lore Olympus', genre: 'romance', genreText: 'Romance', timeAgo: '6h ago', chapter: 'Ch.240', badgeClass: 'new-badge', cover: 'https://upload.wikimedia.org/wikipedia/en/7/72/Lore_Olympus_Banner_Art.png' },
  { id: 5, title: 'Demon Slayer', genre: 'action', genreText: 'Action', timeAgo: '8h ago', chapter: 'Ch.205', badgeClass: '', cover: 'https://upload.wikimedia.org/wikipedia/en/0/09/Demon_Slayer_-_Kimetsu_no_Yaiba%2C_volume_1.jpg' },
  { id: 6, title: 'Jujutsu Kaisen', genre: 'action', genreText: 'Action', timeAgo: '10h ago', chapter: 'Ch.254', badgeClass: 'hot-badge', cover: 'https://upload.wikimedia.org/wikipedia/en/4/46/Jujutsu_kaisen.jpg' },
  { id: 7, title: 'Spy × Family', genre: 'comedy', genreText: 'Comedy', timeAgo: '12h ago', chapter: 'Ch.96', badgeClass: '', cover: 'https://upload.wikimedia.org/wikipedia/en/5/51/Spy_Family_vol_1.jpg' },
  { id: 8, title: 'Chainsaw Man', genre: 'action', genreText: 'Action', timeAgo: '1d ago', chapter: 'Ch.160', badgeClass: 'new-badge', cover: 'https://upload.wikimedia.org/wikipedia/en/2/24/Chainsawman.jpg' },
  { id: 9, title: 'Attack on Titan', genre: 'action', genreText: 'Action', timeAgo: '2d ago', chapter: 'Ch.139', badgeClass: '', cover: 'https://upload.wikimedia.org/wikipedia/en/d/d6/Shingeki_no_Kyojin_manga_volume_1.jpg' },
  { id: 10, title: 'The Beginning After The End', genre: 'fantasy', genreText: 'Fantasy', timeAgo: '4h ago', chapter: 'Ch.175', badgeClass: 'hot-badge', cover: 'https://upload.wikimedia.org/wikipedia/en/8/87/The_Beginning_After_The_End_vol_1.jpg' },
  { id: 11, title: 'My Hero Academia', genre: 'action', genreText: 'Action', timeAgo: '7h ago', chapter: 'Ch.410', badgeClass: '', cover: 'https://upload.wikimedia.org/wikipedia/en/5/5a/Boku_no_Hero_Academia_Volume_1.png' },
  { id: 12, title: 'One Punch Man', genre: 'comedy', genreText: 'Comedy', timeAgo: '9h ago', chapter: 'Ch.198', badgeClass: 'hot-badge', cover: 'https://upload.wikimedia.org/wikipedia/en/c/c3/OnePunchMan_manga_cover.png' }
];

// Hàm tạo thông tin chi tiết bộ truyện khớp chính xác với số chapter
function getComicDetail(id) {
  const base = latestComics.find(c => c.id == id) || trendingComics.find(c => c.id == id) || latestComics[0];
  
  // Trích xuất số chapter mới nhất từ chuỗi (vd "Ch.200" -> 200)
  const latestChapterNum = parseInt((base.chapter || '200').replace(/\D/g, ''), 10) || 200;
  
  // Tạo danh sách chapter từ mới nhất xuống 1 (mẫu hiển thị tối đa 30 chapter mới nhất hoặc full)
  const chaptersCount = Math.min(latestChapterNum, 30);
  const chapters = [];
  
  for (let i = 0; i < chaptersCount; i++) {
    const chapNum = latestChapterNum - i;
    chapters.push({
      id: chapNum,
      number: `Chapter ${chapNum}`,
      title: `The Awakening of Destiny - Part ${chapNum}`,
      date: `2024-07-${(chapNum % 28) + 1}`,
      views: `${(chapNum * 12.4).toFixed(1)}K`
    });
  }

  return {
    id: base.id,
    title: base.title,
    author: 'Chugong & DUBU (REDICE STUDIO)',
    status: 'ONGOING',
    rating: '4.9',
    views: '15.8M',
    likes: '920K',
    tags: [base.genreText || 'Action', 'Fantasy', 'Supernatural', 'Webtoon'],
    cover: base.cover,
    description: `Series cực hot ${base.title}. Trong một thế giới nơi những thợ săn, những con người sở hữu khả năng kỳ diệu phải chiến đấu với những quái vật đáng sợ để bảo vệ nhân loại, một thợ săn yếu đuối tìm thấy chính mình trong một cuộc chiến sinh tồn không tưởng...`,
    totalChapters: latestChapterNum,
    latestChapterNum: latestChapterNum,
    chapters: chapters
  };
}

const MIME_TYPES = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.svg': 'image/svg+xml'
};

const server = http.createServer((req, res) => {
  // CORS Headers
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') {
    res.writeHead(204);
    res.end();
    return;
  }

  const parsedUrl = url.parse(req.url, true);
  const pathname = parsedUrl.pathname;

  // --- API ENDPOINTS ---
  if (pathname === '/api/comics/trending') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify(trendingComics));
    return;
  }

  if (pathname === '/api/comics/latest') {
    const genre = parsedUrl.query.genre;
    let data = latestComics;
    if (genre && genre !== 'all') {
      data = latestComics.filter(c => c.genre.toLowerCase() === genre.toLowerCase());
    }
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify(data));
    return;
  }

  if (pathname === '/api/comics/search') {
    const q = (parsedUrl.query.q || '').toLowerCase();
    const data = latestComics.filter(c => c.title.toLowerCase().includes(q));
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify(data));
    return;
  }

  if (pathname === '/api/comics/detail') {
    const id = parsedUrl.query.id || 1;
    const detail = getComicDetail(id);
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify(detail));
    return;
  }

  // --- STATIC FILES ---
  let reqPath = pathname === '/' ? 'index.html' : pathname;
  let filePath = path.join(__dirname, 'prototype', reqPath);
  if (!fs.existsSync(filePath)) {
    filePath = path.join(__dirname, reqPath);
  }
  const ext = path.extname(filePath).toLowerCase();
  const contentType = MIME_TYPES[ext] || 'text/plain';

  fs.readFile(filePath, (err, content) => {
    if (err) {
      if (err.code === 'ENOENT') {
        res.writeHead(404, { 'Content-Type': 'text/html; charset=utf-8' });
        res.end('<h1>404 Not Found</h1>');
      } else {
        res.writeHead(500);
        res.end(`Server Error: ${err.code}`);
      }
    } else {
      res.writeHead(200, { 'Content-Type': contentType });
      res.end(content);
    }
  });
});

server.listen(PORT, () => {
  console.log(`Server dang chay tai: http://localhost:${PORT}`);
});
