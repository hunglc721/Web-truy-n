// WebComics App JavaScript - Complete Interactive Header, Admin Dashboard, Schedule, Originals & API Integration

const API_BASE_URL = 'http://localhost:3000/api';

// Dữ liệu Mock API 35+ bộ truyện chuẩn đa dạng thể loại
const MOCK_TRENDING = [
  { id: 1, title: 'Solo Leveling', genre: 'Action · Fantasy', cover: 'https://upload.wikimedia.org/wikipedia/en/6/6c/Solo_Leveling_Volume_1_Cover.jpg', rank: 1, rankClass: 'r1' },
  { id: 2, title: 'Tower of God', genre: 'Fantasy · Mystery', cover: 'https://upload.wikimedia.org/wikipedia/en/7/7d/Tower_of_God_Volume_1_Cover.jpg', rank: 2, rankClass: 'r2' },
  { id: 3, title: "Omniscient Reader's Viewpoint", genre: 'Action · Fantasy', cover: 'https://upload.wikimedia.org/wikipedia/en/6/69/Omniscient_Reader%27s_Viewpoint_Volume_1_Cover.jpg', rank: 3, rankClass: 'r3' },
  { id: 4, title: 'Lore Olympus', genre: 'Romance · Mythology', cover: 'https://upload.wikimedia.org/wikipedia/en/7/72/Lore_Olympus_Banner_Art.png', rank: 4, rankClass: '' },
  { id: 5, title: 'Demon Slayer: Kimetsu no Yaiba', genre: 'Action · Supernatural', cover: 'https://upload.wikimedia.org/wikipedia/en/0/09/Demon_Slayer_-_Kimetsu_no_Yaiba%2C_volume_1.jpg', rank: 5, rankClass: '' },
  { id: 6, title: 'Jujutsu Kaisen', genre: 'Action · Fantasy', cover: 'https://upload.wikimedia.org/wikipedia/en/4/46/Jujutsu_kaisen.jpg', rank: 6, rankClass: '' },
  { id: 7, title: 'Spy × Family', genre: 'Comedy · Action', cover: 'https://upload.wikimedia.org/wikipedia/en/5/51/Spy_Family_vol_1.jpg', rank: 7, rankClass: '' },
  { id: 8, title: 'Chainsaw Man', genre: 'Action · Horror', cover: 'https://upload.wikimedia.org/wikipedia/en/2/24/Chainsawman.jpg', rank: 8, rankClass: '' },
  { id: 9, title: 'Attack on Titan', genre: 'Action · Drama', cover: 'https://upload.wikimedia.org/wikipedia/en/d/d6/Shingeki_no_Kyojin_manga_volume_1.jpg', rank: 9, rankClass: '' },
  { id: 10, title: 'The Beginning After The End', genre: 'Fantasy · Isekai', cover: 'https://upload.wikimedia.org/wikipedia/en/8/87/The_Beginning_After_The_End_vol_1.jpg', rank: 10, rankClass: '' }
];

let MOCK_LATEST = [
  { id: 1, title: 'Solo Leveling', genre: 'action', genreText: 'Action · Fantasy', author: 'Chugong & DUBU', rating: '4.9', views: '15.8M', likes: '920K', status: 'COMPLETED', timeAgo: '2h ago', chapter: 'Ch.200', badgeClass: 'hot-badge', cover: 'https://upload.wikimedia.org/wikipedia/en/6/6c/Solo_Leveling_Volume_1_Cover.jpg' },
  { id: 2, title: 'Tower of God', genre: 'fantasy', genreText: 'Fantasy · Mystery', author: 'SIU', rating: '4.8', views: '12.4M', likes: '780K', status: 'ONGOING', timeAgo: '3h ago', chapter: 'Ch.590', badgeClass: '', cover: 'https://upload.wikimedia.org/wikipedia/en/7/7d/Tower_of_God_Volume_1_Cover.jpg' },
  { id: 3, title: "Omniscient Reader's Viewpoint", genre: 'action', genreText: 'Action · Fantasy', author: 'singNsong', rating: '4.9', views: '11.2M', likes: '850K', status: 'ONGOING', timeAgo: '5h ago', chapter: 'Ch.185', badgeClass: 'hot-badge', cover: 'https://upload.wikimedia.org/wikipedia/en/6/69/Omniscient_Reader%27s_Viewpoint_Volume_1_Cover.jpg' },
  { id: 4, title: 'Lore Olympus', genre: 'romance', genreText: 'Romance · Mythology', author: 'Rachel Smythe', rating: '4.7', views: '9.8M', likes: '640K', status: 'COMPLETED', timeAgo: '6h ago', chapter: 'Ch.280', badgeClass: 'new-badge', cover: 'https://upload.wikimedia.org/wikipedia/en/7/72/Lore_Olympus_Banner_Art.png' },
  { id: 5, title: 'Demon Slayer: Kimetsu no Yaiba', genre: 'action', genreText: 'Action · Supernatural', author: 'Koyoharu Gotouge', rating: '4.9', views: '18.5M', likes: '1.2M', status: 'COMPLETED', timeAgo: '8h ago', chapter: 'Ch.205', badgeClass: '', cover: 'https://upload.wikimedia.org/wikipedia/en/0/09/Demon_Slayer_-_Kimetsu_no_Yaiba%2C_volume_1.jpg' },
  { id: 6, title: 'Jujutsu Kaisen', genre: 'action', genreText: 'Action · Fantasy', author: 'Gege Akutami', rating: '4.8', views: '16.2M', likes: '990K', status: 'ONGOING', timeAgo: '10h ago', chapter: 'Ch.265', badgeClass: 'hot-badge', cover: 'https://upload.wikimedia.org/wikipedia/en/4/46/Jujutsu_kaisen.jpg' },
  { id: 7, title: 'Spy × Family', genre: 'comedy', genreText: 'Comedy · Action', author: 'Tatsuya Endo', rating: '4.9', views: '14.1M', likes: '880K', status: 'ONGOING', timeAgo: '12h ago', chapter: 'Ch.102', badgeClass: '', cover: 'https://upload.wikimedia.org/wikipedia/en/5/51/Spy_Family_vol_1.jpg' },
  { id: 8, title: 'Chainsaw Man', genre: 'action', genreText: 'Action · Horror', author: 'Tatsuki Fujimoto', rating: '4.8', views: '13.7M', likes: '910K', status: 'ONGOING', timeAgo: '1d ago', chapter: 'Ch.172', badgeClass: 'new-badge', cover: 'https://upload.wikimedia.org/wikipedia/en/2/24/Chainsawman.jpg' },
  { id: 9, title: 'Attack on Titan', genre: 'action', genreText: 'Action · Drama', author: 'Hajime Isayama', rating: '4.9', views: '22.0M', likes: '1.5M', status: 'COMPLETED', timeAgo: '2d ago', chapter: 'Ch.139', badgeClass: '', cover: 'https://upload.wikimedia.org/wikipedia/en/d/d6/Shingeki_no_Kyojin_manga_volume_1.jpg' },
  { id: 10, title: 'The Beginning After The End', genre: 'fantasy', genreText: 'Fantasy · Isekai', author: 'TurtleMe', rating: '4.9', views: '10.5M', likes: '790K', status: 'ONGOING', timeAgo: '4h ago', chapter: 'Ch.180', badgeClass: 'hot-badge', cover: 'https://upload.wikimedia.org/wikipedia/en/8/87/The_Beginning_After_The_End_vol_1.jpg' },
  { id: 11, title: 'unOrdinary', genre: 'supernatural', genreText: 'Supernatural · Drama', author: 'uru-chan', rating: '4.7', views: '8.4M', likes: '520K', status: 'ONGOING', timeAgo: '5h ago', chapter: 'Ch.340', badgeClass: '', cover: 'https://images.unsplash.com/photo-1563089145-599997674d42?w=600&auto=format&fit=crop&q=80' },
  { id: 12, title: 'True Beauty', genre: 'romance', genreText: 'Romance · Drama', author: 'Yaongyi', rating: '4.6', views: '11.8M', likes: '710K', status: 'COMPLETED', timeAgo: '7h ago', chapter: 'Ch.223', badgeClass: 'new-badge', cover: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=600&auto=format&fit=crop&q=80' },
  { id: 13, title: 'Midnight Poppyland', genre: 'romance', genreText: 'Romance · Action', author: 'Lilydusk', rating: '4.8', views: '6.2M', likes: '430K', status: 'ONGOING', timeAgo: '9h ago', chapter: 'Ch.120', badgeClass: '', cover: 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=600&auto=format&fit=crop&q=80' },
  { id: 14, title: 'Lookism', genre: 'drama', genreText: 'Drama · Martial Arts', author: 'Taejun Pak', rating: '4.8', views: '15.3M', likes: '950K', status: 'ONGOING', timeAgo: '11h ago', chapter: 'Ch.510', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&auto=format&fit=crop&q=80' },
  { id: 15, title: 'My Inmost Secret', genre: 'thriller', genreText: 'Thriller · Mystery', author: 'Hanamaki', rating: '4.7', views: '4.9M', likes: '310K', status: 'COMPLETED', timeAgo: '1d ago', chapter: 'Ch.108', badgeClass: '', cover: 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=600&auto=format&fit=crop&q=80' },
  { id: 16, title: 'Sweet Home', genre: 'horror', genreText: 'Horror · Thriller', author: 'Carnby Kim', rating: '4.9', views: '9.2M', likes: '680K', status: 'COMPLETED', timeAgo: '1d ago', chapter: 'Ch.140', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1509248961158-e54f6934749c?w=600&auto=format&fit=crop&q=80' },
  { id: 17, title: 'Eleceed', genre: 'comedy', genreText: 'Comedy · Action', author: 'Zhena & Son Jeho', rating: '4.9', views: '8.7M', likes: '620K', status: 'ONGOING', timeAgo: '3h ago', chapter: 'Ch.305', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=600&auto=format&fit=crop&q=80' },
  { id: 18, title: 'The Remarried Empress', genre: 'fantasy', genreText: 'Fantasy · Romance', author: 'Alphatart', rating: '4.8', views: '7.6M', likes: '540K', status: 'ONGOING', timeAgo: '6h ago', chapter: 'Ch.175', badgeClass: '', cover: 'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=600&auto=format&fit=crop&q=80' },
  { id: 19, title: 'Doom Breaker', genre: 'action', genreText: 'Action · Fantasy', author: 'Blue-King', rating: '4.8', views: '5.8M', likes: '410K', status: 'ONGOING', timeAgo: '8h ago', chapter: 'Ch.110', badgeClass: 'new-badge', cover: 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=600&auto=format&fit=crop&q=80' },
  { id: 20, title: 'Bastard', genre: 'thriller', genreText: 'Thriller · Psychological', author: 'Carnby Kim', rating: '4.9', views: '8.1M', likes: '590K', status: 'COMPLETED', timeAgo: '2d ago', chapter: 'Ch.93', badgeClass: '', cover: 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=600&auto=format&fit=crop&q=80' },
  { id: 21, title: 'Wind Breaker', genre: 'sports', genreText: 'Sports · Drama', author: 'Jo Yongseok', rating: '4.9', views: '12.8M', likes: '840K', status: 'ONGOING', timeAgo: '5h ago', chapter: 'Ch.490', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1485965120184-e220f721d03e?w=600&auto=format&fit=crop&q=80' },
  { id: 22, title: 'Nano Machine', genre: 'cultivation', genreText: 'Martial Arts · Sci-Fi', author: 'Han-Jo', rating: '4.8', views: '9.3M', likes: '670K', status: 'ONGOING', timeAgo: '4h ago', chapter: 'Ch.215', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600&auto=format&fit=crop&q=80' },
  { id: 23, title: 'Survival Story of a Sword King', genre: 'isekai', genreText: 'Isekai · Action', author: 'Yrap', rating: '4.7', views: '6.4M', likes: '420K', status: 'ONGOING', timeAgo: '10h ago', chapter: 'Ch.205', badgeClass: '', cover: 'https://images.unsplash.com/photo-1563089145-599997674d42?w=600&auto=format&fit=crop&q=80' },
  { id: 24, title: "Let's Play", genre: 'romance', genreText: 'Romance · Slice of Life', author: 'Mongie', rating: '4.6', views: '5.2M', likes: '370K', status: 'ONGOING', timeAgo: '1d ago', chapter: 'Ch.170', badgeClass: '', cover: 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=600&auto=format&fit=crop&q=80' },
  { id: 25, title: 'SubZero', genre: 'fantasy', genreText: 'Fantasy · Romance', author: 'Junepurrr', rating: '4.7', views: '5.9M', likes: '410K', status: 'ONGOING', timeAgo: '12h ago', chapter: 'Ch.165', badgeClass: 'new-badge', cover: 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?w=600&auto=format&fit=crop&q=80' },
  { id: 26, title: 'The Boxer', genre: 'sports', genreText: 'Sports · Action', author: 'JH', rating: '4.9', views: '7.8M', likes: '610K', status: 'COMPLETED', timeAgo: '3d ago', chapter: 'Ch.124', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1517649763962-0c623266010b?w=600&auto=format&fit=crop&q=80' },
  { id: 27, title: 'The God of High School', genre: 'action', genreText: 'Action · Martial Arts', author: 'Yongje Park', rating: '4.7', views: '14.5M', likes: '890K', status: 'COMPLETED', timeAgo: '2d ago', chapter: 'Ch.570', badgeClass: '', cover: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=600&auto=format&fit=crop&q=80' },
  { id: 28, title: 'Return of the Mount Hua Sect', genre: 'cultivation', genreText: 'Martial Arts · Historical', author: 'Biga & LICO', rating: '4.9', views: '11.4M', likes: '830K', status: 'ONGOING', timeAgo: '2h ago', chapter: 'Ch.130', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=600&auto=format&fit=crop&q=80' },
  { id: 29, title: "Skeleton Soldier Couldn't Protect the Dungeon", genre: 'isekai', genreText: 'Isekai · Supernatural', author: 'Kangyoo', rating: '4.7', views: '6.1M', likes: '400K', status: 'ONGOING', timeAgo: '7h ago', chapter: 'Ch.280', badgeClass: '', cover: 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=600&auto=format&fit=crop&q=80' },
  { id: 30, title: 'Leveling Up With the Gods', genre: 'fantasy', genreText: 'Fantasy · Action', author: 'Saeyoung', rating: '4.8', views: '7.3M', likes: '510K', status: 'ONGOING', timeAgo: '5h ago', chapter: 'Ch.115', badgeClass: 'new-badge', cover: 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=600&auto=format&fit=crop&q=80' },
  { id: 31, title: 'Mercenary Enrollment', genre: 'action', genreText: 'Action · School', author: 'YC & Rakhyun', rating: '4.9', views: '10.8M', likes: '770K', status: 'ONGOING', timeAgo: '3h ago', chapter: 'Ch.195', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&auto=format&fit=crop&q=80' },
  { id: 32, title: 'Overgeared', genre: 'comedy', genreText: 'Virtual Reality · Comedy', author: 'Park Saenal', rating: '4.8', views: '8.9M', likes: '630K', status: 'ONGOING', timeAgo: '6h ago', chapter: 'Ch.230', badgeClass: '', cover: 'https://images.unsplash.com/photo-1538481199705-c710c4e965fc?w=600&auto=format&fit=crop&q=80' },
  { id: 33, title: 'Second Life Ranker', genre: 'action', genreText: 'Action · Fantasy', author: 'Nong Nong', rating: '4.8', views: '7.1M', likes: '490K', status: 'ONGOING', timeAgo: '8h ago', chapter: 'Ch.170', badgeClass: '', cover: 'https://images.unsplash.com/photo-1563089145-599997674d42?w=600&auto=format&fit=crop&q=80' },
  { id: 34, title: 'Reincarnation of the Suicidal Battle God', genre: 'action', genreText: 'Action · Reincarnation', author: 'Cheong-Nok', rating: '4.9', views: '6.7M', likes: '470K', status: 'ONGOING', timeAgo: '4h ago', chapter: 'Ch.105', badgeClass: 'new-badge', cover: 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=600&auto=format&fit=crop&q=80' },
  { id: 35, title: 'The Greatest Estate Developer', genre: 'comedy', genreText: 'Isekai · Comedy', author: 'BK_Moon', rating: '4.9', views: '12.1M', likes: '940K', status: 'ONGOING', timeAgo: '2h ago', chapter: 'Ch.150', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=600&auto=format&fit=crop&q=80' }
];

// Dữ liệu Lịch phát sóng theo ngày
const SCHEDULE_DATA = {
  monday: [
    { id: 1, title: 'Solo Leveling', author: 'Updated 2h ago', genre: 'Action', chapter: 'Ch.200', desc: 'Chapter 200: Shadow Monarch ultimate battle.', cover: 'https://upload.wikimedia.org/wikipedia/en/6/6c/Solo_Leveling_Volume_1_Cover.jpg' },
    { id: 4, title: 'Lore Olympus', author: 'Updated 6h ago', genre: 'Romance', chapter: 'Ch.280', desc: 'Chapter 280: The Underworld coronation.', cover: 'https://upload.wikimedia.org/wikipedia/en/7/72/Lore_Olympus_Banner_Art.png' },
    { id: 7, title: 'Spy × Family', author: 'Updated 12h ago', genre: 'Comedy', chapter: 'Ch.102', desc: 'Chapter 102: Anya secret mission.', cover: 'https://upload.wikimedia.org/wikipedia/en/5/51/Spy_Family_vol_1.jpg' },
    { id: 11, title: 'unOrdinary', author: 'Updated 5h ago', genre: 'Supernatural', chapter: 'Ch.340', desc: 'Chapter 340: Wellston High hierarchy war.', cover: 'https://images.unsplash.com/photo-1563089145-599997674d42?w=600&auto=format&fit=crop&q=80' },
    { id: 28, title: 'Return of the Mount Hua Sect', author: 'Updated 2h ago', genre: 'Martial Arts', chapter: 'Ch.130', desc: 'Chapter 130: Mount Hua revival tournament.', cover: 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=600&auto=format&fit=crop&q=80' }
  ],
  tuesday: [
    { id: 2, title: 'Tower of God', author: 'Updated 3h ago', genre: 'Fantasy', chapter: 'Ch.590', desc: 'Chapter 590: Floor 77 War climax.', cover: 'https://upload.wikimedia.org/wikipedia/en/7/7d/Tower_of_God_Volume_1_Cover.jpg' },
    { id: 5, title: 'Demon Slayer', author: 'Updated 8h ago', genre: 'Supernatural', chapter: 'Ch.205', desc: 'Chapter 205: Final sunrise after battle.', cover: 'https://upload.wikimedia.org/wikipedia/en/0/09/Demon_Slayer_-_Kimetsu_no_Yaiba%2C_volume_1.jpg' },
    { id: 8, title: 'Chainsaw Man', author: 'Updated 1d ago', genre: 'Horror', chapter: 'Ch.172', desc: 'Chapter 172: Devil fusion awakening.', cover: 'https://upload.wikimedia.org/wikipedia/en/2/24/Chainsawman.jpg' },
    { id: 17, title: 'Eleceed', author: 'Updated 3h ago', genre: 'Comedy', chapter: 'Ch.305', desc: 'Chapter 305: Kayden feline power clash.', cover: 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=600&auto=format&fit=crop&q=80' },
    { id: 31, title: 'Mercenary Enrollment', author: 'Updated 3h ago', genre: 'Action', chapter: 'Ch.195', desc: 'Chapter 195: Ijin bodyguard operation.', cover: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&auto=format&fit=crop&q=80' }
  ],
  wednesday: [
    { id: 3, title: 'Omniscient Reader', author: 'Updated 5h ago', genre: 'Fantasy', chapter: 'Ch.185', desc: 'Chapter 185: Demon King scenario.', cover: 'https://upload.wikimedia.org/wikipedia/en/6/69/Omniscient_Reader%27s_Viewpoint_Volume_1_Cover.jpg' },
    { id: 6, title: 'Jujutsu Kaisen', author: 'Updated 10h ago', genre: 'Action', chapter: 'Ch.265', desc: 'Chapter 265: Shinjuku showdown.', cover: 'https://upload.wikimedia.org/wikipedia/en/4/46/Jujutsu_kaisen.jpg' },
    { id: 9, title: 'Attack on Titan', author: 'Updated 2d ago', genre: 'Drama', chapter: 'Ch.139', desc: 'Chapter 139: Freedom finale.', cover: 'https://upload.wikimedia.org/wikipedia/en/d/d6/Shingeki_no_Kyojin_manga_volume_1.jpg' },
    { id: 22, title: 'Nano Machine', author: 'Updated 4h ago', genre: 'Martial Arts', chapter: 'Ch.215', desc: 'Chapter 215: Demon Cult Heavenly Lord.', cover: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600&auto=format&fit=crop&q=80' },
    { id: 35, title: 'The Greatest Estate Developer', author: 'Updated 2h ago', genre: 'Comedy', chapter: 'Ch.150', desc: 'Chapter 150: Lloyd civil engineering magic.', cover: 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=600&auto=format&fit=crop&q=80' }
  ],
  thursday: [
    { id: 1, title: 'Solo Leveling', author: 'Updated 2h ago', genre: 'Action', chapter: 'Ch.200', desc: 'Chapter 200: Shadow Monarch ultimate battle.', cover: 'https://upload.wikimedia.org/wikipedia/en/6/6c/Solo_Leveling_Volume_1_Cover.jpg' },
    { id: 2, title: 'Tower of God', author: 'Updated 4h ago', genre: 'Fantasy', chapter: 'Ch.590', desc: 'Chapter 590: Floor 77 War climax.', cover: 'https://upload.wikimedia.org/wikipedia/en/7/7d/Tower_of_God_Volume_1_Cover.jpg' },
    { id: 3, title: 'Omniscient Reader', author: 'Updated 5h ago', genre: 'Action', chapter: 'Ch.185', desc: 'Chapter 185: The Demon King scenario.', cover: 'https://upload.wikimedia.org/wikipedia/en/6/69/Omniscient_Reader%27s_Viewpoint_Volume_1_Cover.jpg' },
    { id: 10, title: 'The Beginning After The End', author: 'Updated 4h ago', genre: 'Fantasy', chapter: 'Ch.180', desc: 'Chapter 180: Relictombs exploration.', cover: 'https://upload.wikimedia.org/wikipedia/en/8/87/The_Beginning_After_The_End_vol_1.jpg' },
    { id: 14, title: 'Lookism', author: 'Updated 11h ago', genre: 'Drama', chapter: 'Ch.510', desc: 'Chapter 510: Workers First Affiliate climax.', cover: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&auto=format&fit=crop&q=80' },
    { id: 21, title: 'Wind Breaker', author: 'Updated 5h ago', genre: 'Sports', chapter: 'Ch.490', desc: 'Chapter 490: Hummingbird final lap.', cover: 'https://images.unsplash.com/photo-1485965120184-e220f721d03e?w=600&auto=format&fit=crop&q=80' }
  ],
  friday: [
    { id: 4, title: 'Lore Olympus', author: 'Updated 1h ago', genre: 'Romance', chapter: 'Ch.280', desc: 'Chapter 280: Love story unfolds.', cover: 'https://upload.wikimedia.org/wikipedia/en/7/72/Lore_Olympus_Banner_Art.png' },
    { id: 7, title: 'Spy × Family', author: 'Updated 4h ago', genre: 'Comedy', chapter: 'Ch.102', desc: 'Chapter 102: Operation Strix update.', cover: 'https://upload.wikimedia.org/wikipedia/en/5/51/Spy_Family_vol_1.jpg' },
    { id: 12, title: 'True Beauty', author: 'Updated 7h ago', genre: 'Romance', chapter: 'Ch.223', desc: 'Chapter 223: Makeup artist career high.', cover: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=600&auto=format&fit=crop&q=80' },
    { id: 18, title: 'The Remarried Empress', author: 'Updated 6h ago', genre: 'Fantasy', chapter: 'Ch.175', desc: 'Chapter 175: Empress Navier kingdom decree.', cover: 'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=600&auto=format&fit=crop&q=80' }
  ],
  saturday: [
    { id: 1, title: 'Solo Leveling', author: 'Updated 3h ago', genre: 'Action', chapter: 'Ch.201', desc: 'Chapter 201: Arise awakening.', cover: 'https://upload.wikimedia.org/wikipedia/en/6/6c/Solo_Leveling_Volume_1_Cover.jpg' },
    { id: 6, title: 'Jujutsu Kaisen', author: 'Updated 5h ago', genre: 'Action', chapter: 'Ch.265', desc: 'Chapter 265: Black flash strike.', cover: 'https://upload.wikimedia.org/wikipedia/en/4/46/Jujutsu_kaisen.jpg' },
    { id: 16, title: 'Sweet Home', author: 'Updated 1d ago', genre: 'Horror', chapter: 'Ch.140', desc: 'Chapter 140: Monsterization survival.', cover: 'https://images.unsplash.com/photo-1509248961158-e54f6934749c?w=600&auto=format&fit=crop&q=80' },
    { id: 26, title: 'The Boxer', author: 'Updated 3d ago', genre: 'Sports', chapter: 'Ch.124', desc: 'Chapter 124: Yu world championship belt.', cover: 'https://images.unsplash.com/photo-1517649763962-0c623266010b?w=600&auto=format&fit=crop&q=80' }
  ],
  sunday: [
    { id: 2, title: 'Tower of God', author: 'Updated 2h ago', genre: 'Fantasy', chapter: 'Ch.591', desc: 'Chapter 591: High Ranker duel.', cover: 'https://upload.wikimedia.org/wikipedia/en/7/7d/Tower_of_God_Volume_1_Cover.jpg' },
    { id: 3, title: 'Omniscient Reader', author: 'Updated 6h ago', genre: 'Action', chapter: 'Ch.186', desc: 'Chapter 186: Constellation alliance.', cover: 'https://upload.wikimedia.org/wikipedia/en/6/69/Omniscient_Reader%27s_Viewpoint_Volume_1_Cover.jpg' },
    { id: 19, title: 'Doom Breaker', author: 'Updated 8h ago', genre: 'Action', chapter: 'Ch.110', desc: 'Chapter 110: Dragon raid battle.', cover: 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=600&auto=format&fit=crop&q=80' },
    { id: 30, title: 'Leveling Up With the Gods', author: 'Updated 5h ago', genre: 'Fantasy', chapter: 'Ch.115', desc: 'Chapter 115: Olympus trial victory.', cover: 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=600&auto=format&fit=crop&q=80' }
  ]
};

document.addEventListener('DOMContentLoaded', () => {

  // --- 1. HEADER & MODALS ---
  const loginModal = document.getElementById('login-modal');
  const downloadModal = document.getElementById('download-modal');
  const libraryModal = document.getElementById('library-modal');
  const storeModal = document.getElementById('store-modal');
  const publishModal = document.getElementById('publish-modal');

  const loginBtn = document.getElementById('login-btn');
  const downloadBtn = document.getElementById('download-app-btn');
  const libraryBtn = document.getElementById('library-btn');
  const storeBtn = document.getElementById('store-btn');
  const publishLink = document.getElementById('publish-link');

  function openModal(modal) {
    if (modal) modal.classList.remove('hidden');
  }

  function closeModal(modal) {
    if (modal) modal.classList.add('hidden');
  }

  if (loginBtn) loginBtn.addEventListener('click', () => openModal(loginModal));
  if (downloadBtn) downloadBtn.addEventListener('click', () => openModal(downloadModal));
  if (libraryBtn) {
    libraryBtn.addEventListener('click', () => {
      renderLibraryItems();
      openModal(libraryModal);
    });
  }
  if (storeBtn) storeBtn.addEventListener('click', () => openModal(storeModal));
  if (publishLink) {
    publishLink.addEventListener('click', (e) => {
      e.preventDefault();
      openModal(publishModal);
    });
  }

  const modalCloseBtns = [
    { btn: 'login-modal-close', modal: loginModal },
    { btn: 'download-modal-close', modal: downloadModal },
    { btn: 'library-modal-close', modal: libraryModal },
    { btn: 'store-modal-close', modal: storeModal },
    { btn: 'publish-modal-close', modal: publishModal }
  ];

  modalCloseBtns.forEach(item => {
    const el = document.getElementById(item.btn);
    if (el) el.addEventListener('click', () => closeModal(item.modal));
  });

  [loginModal, downloadModal, libraryModal, storeModal, publishModal].forEach(modal => {
    if (modal) {
      modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal(modal);
      });
    }
  });

  // LOG IN LOGIC & ROLE SWITCHING
  let selectedRole = 'user';
  const roleUserBtn = document.getElementById('role-user-btn');
  const roleAdminBtn = document.getElementById('role-admin-btn');
  const loginEmailInput = document.getElementById('login-email');

  if (roleUserBtn && roleAdminBtn) {
    roleUserBtn.addEventListener('click', () => {
      selectedRole = 'user';
      roleUserBtn.classList.add('active');
      roleAdminBtn.classList.remove('active');
      loginEmailInput.value = 'user@webcomics.com';
    });

    roleAdminBtn.addEventListener('click', () => {
      selectedRole = 'admin';
      roleAdminBtn.classList.add('active');
      roleUserBtn.classList.remove('active');
      loginEmailInput.value = 'admin@webcomics.com';
    });
  }

  const loginForm = document.getElementById('login-form');
  if (loginForm) {
    loginForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const user = {
        name: selectedRole === 'admin' ? 'Admin Manager' : 'Alex Reader',
        email: loginEmailInput.value,
        role: selectedRole,
        isLoggedIn: true
      };
      localStorage.setItem('webcomics_user', JSON.stringify(user));
      closeModal(loginModal);
      checkUserAuth();
      alert(`Xin chào ${user.name}! Bạn đã đăng nhập thành công với vai trò ${selectedRole.toUpperCase()}.`);
    });
  }

  function checkUserAuth() {
    const savedUserStr = localStorage.getItem('webcomics_user');
    let user = null;
    try { user = JSON.parse(savedUserStr); } catch(e){}

    const loginBtn = document.getElementById('login-btn');
    if (!loginBtn) return;

    if (user && user.isLoggedIn) {
      if (user.role === 'admin') {
        loginBtn.outerHTML = `
          <div style="display:flex; align-items:center; gap:8px;">
            <a href="admin.html" class="btn btn-login" style="background:linear-gradient(135deg,#6c63ff,#ff2a6d); color:#fff; text-decoration:none; border:none; padding:7px 14px; font-size:12px; font-weight:700; border-radius:8px; box-shadow:0 4px 12px rgba(108,99,255,.3);">🛡️ Admin Dashboard</a>
            <div class="user-profile-badge" style="display:flex; align-items:center; gap:8px; background:rgba(255,255,255,.06); padding:5px 12px; border-radius:20px; border:1px solid rgba(255,255,255,.1);">
              <div class="user-avatar-circle" style="width:26px; height:26px; border-radius:50%; background:linear-gradient(135deg,#6c63ff,#ff2a6d); color:#fff; font-weight:800; font-size:11px; display:flex; align-items:center; justify-content:center;">AD</div>
              <span style="font-size:12.5px; font-weight:700; color:#e4e6f0;">${escapeHtml(user.name)}</span>
              <button id="logout-btn" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:14px; font-weight:700; padding:2px 6px; margin-left:4px;" title="Đăng Xuất">✕</button>
            </div>
          </div>
        `;
      } else {
        loginBtn.outerHTML = `
          <div class="user-profile-badge" style="display:flex; align-items:center; gap:8px; background:rgba(255,255,255,.06); padding:5px 12px; border-radius:20px; border:1px solid rgba(255,255,255,.1);">
            <div class="user-avatar-circle" style="width:26px; height:26px; border-radius:50%; background:linear-gradient(135deg,#10b981,#3b82f6); color:#fff; font-weight:800; font-size:11px; display:flex; align-items:center; justify-content:center;">US</div>
            <span style="font-size:12.5px; font-weight:700; color:#e4e6f0;">${escapeHtml(user.name)}</span>
            <button id="logout-btn" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:14px; font-weight:700; padding:2px 6px; margin-left:4px;" title="Đăng Xuất">✕</button>
          </div>
        `;
      }

      const logoutBtn = document.getElementById('logout-btn');
      if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
          localStorage.removeItem('webcomics_user');
          window.location.reload();
        });
      }
    }
  }

  checkUserAuth();


  // --- ADMIN INTER-MODULE COORDINATION & CROSS-LINKING ---
  const adminComicsTbody = document.getElementById('admin-comics-tbody');
  const chapComicSelect = document.getElementById('chap-comic-select');

  if (adminComicsTbody || chapComicSelect) {
    function renderAdminComics() {
      if (chapComicSelect) {
        chapComicSelect.innerHTML = MOCK_LATEST.map(c => `
          <option value="${c.id}">${escapeHtml(c.title)} (${c.chapter})</option>
        `).join('');
      }

      if (adminComicsTbody) {
        adminComicsTbody.innerHTML = MOCK_LATEST.map(c => `
          <tr>
            <td>
              <img src="${c.cover}" style="width:36px; height:48px; object-fit:cover; border-radius:5px" />
            </td>
            <td>
              <a href="detail.html?id=${c.id}" target="_blank" style="font-weight:700; color:var(--text); text-decoration:none">
                ${escapeHtml(c.title)}
              </a>
            </td>
            <td>
              <span class="badge badge-p" style="font-size:11px">${escapeHtml(c.genreText || c.genre)}</span>
            </td>
            <td style="text-align:center">
              <span class="badge" style="background:rgba(34,197,94,0.15); color:#22c55e">🟢 ONGOING</span>
            </td>
            <td style="text-align:center; font-weight:700">
              ${c.chapter}
            </td>
            <td style="text-align:right">
              <div style="display:flex; gap:5px; justify-content:flex-end; flex-wrap:wrap">
                <a href="admin-comments.html?comic=${encodeURIComponent(c.title)}" class="tbtn tbtn-ghost" style="padding:4px 8px; font-size:11px" title="Xem bình luận bộ này">
                  💬 Bình luận
                </a>
                <a href="admin-reports.html?comic=${encodeURIComponent(c.title)}" class="tbtn tbtn-ghost" style="padding:4px 8px; font-size:11px" title="Xem báo cáo lỗi bộ này">
                  ⚠️ Báo cáo
                </a>
                <a href="admin-schedules.html?comic=${encodeURIComponent(c.title)}" class="tbtn tbtn-ghost" style="padding:4px 8px; font-size:11px" title="Xem lịch chiếu bộ này">
                  📅 Lịch
                </a>
                <a href="#section-add-chapter" onclick="selectComicForChapter(${c.id})" class="tbtn tbtn-primary" style="padding:4px 8px; font-size:11px">
                  ➕ Chap
                </a>
              </div>
            </td>
          </tr>
        `).join('');
      }
    }

    window.selectComicForChapter = function(comicId) {
      if (chapComicSelect) {
        chapComicSelect.value = comicId;
        const addChapSection = document.getElementById('section-add-chapter');
        if (addChapSection) addChapSection.scrollIntoView({ behavior: 'smooth' });
      }
    };

    renderAdminComics();
  }

  // --- URL PARAMETER FILTER HANDLERS FOR CROSS-LINKING ---
  const urlParams = new URLSearchParams(window.location.search);
  const filterComicParam = urlParams.get('comic');
  const filterUserParam = urlParams.get('user');

  // Comments Page Filter
  const cmtSearchInput = document.getElementById('cmt-search');
  if (cmtSearchInput && filterComicParam) {
    cmtSearchInput.value = filterComicParam;
    const cmtHeader = document.querySelector('.ph p');
    if (cmtHeader) cmtHeader.innerHTML += ` <strong style="color:var(--primary); font-size:14px">└ Đang lọc bình luận cho bộ: "${escapeHtml(filterComicParam)}"</strong>`;
    setTimeout(() => {
      if (typeof window.filterCmt === 'function') window.filterCmt();
    }, 100);
  }

  // Reports Page Filter
  if (window.location.pathname.includes('admin-reports.html') && filterComicParam) {
    const reportRows = document.querySelectorAll('tbody tr');
    reportRows.forEach(row => {
      if (!row.innerText.toLowerCase().includes(filterComicParam.toLowerCase())) {
        row.style.display = 'none';
      }
    });
    const ph = document.querySelector('.ph p');
    if (ph) ph.innerHTML += ` <strong style="color:var(--primary); font-size:14px">└ Đang lọc báo cáo lỗi cho bộ: "${escapeHtml(filterComicParam)}"</strong>`;
  }

  // Users Page Filter & Drawer Auto Open
  if (window.location.pathname.includes('admin-users.html') && filterUserParam) {
    const searchUserElem = document.getElementById('search-input-user');
    if (searchUserElem) {
      searchUserElem.value = filterUserParam;
      searchUserElem.dispatchEvent(new Event('keyup'));
    }
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function renderLibraryItems() {
    const libContainer = document.getElementById('library-items-list');
    if (!libContainer) return;

    // Đọc từ localStorage — detail.html lưu dạng object {id, title, cover, chapter, url}
    let savedRaw = [];
    try {
      savedRaw = JSON.parse(localStorage.getItem('wc_library_comics') || '[]');
      if (!Array.isArray(savedRaw)) savedRaw = [];
    } catch(e) { savedRaw = []; }

    // Chuẩn hoá: hỗ trợ cả mảng ID (cũ) lẫn mảng object (mới)
    const savedItems = savedRaw.map(item => {
      if (typeof item === 'object' && item !== null && item.id) {
        return item; // object đầy đủ từ detail.html
      }
      // Fallback: nếu chỉ là ID thì tra catalog
      const found = MOCK_LATEST.find(c => String(c.id) === String(item));
      if (found) return { id: found.id, title: found.title, cover: found.cover, chapter: found.chapter, url: `detail.html?id=${found.id}` };
      return null;
    }).filter(Boolean);

    // Nếu không có gì
    if (savedItems.length === 0) {
      libContainer.innerHTML = `
        <div style="text-align:center; padding:32px 16px; color:#7b7f9e;">
          <div style="font-size:36px; margin-bottom:8px;">📚</div>
          <div style="font-weight:700; color:#e4e6f0; font-size:14px;">Tủ sách đang trống</div>
          <div style="font-size:12px; margin-top:4px; color:#9ca3af;">Hãy nhấn <strong>"📚 Theo Dõi Truyện"</strong> ở các bộ truyện để lưu vào đây nhé!</div>
        </div>`;
      return;
    }

    libContainer.innerHTML = savedItems.map(item => `
      <div style="display:flex; align-items:center; justify-content:space-between; background:rgba(255,255,255,.05); padding:10px 14px; border-radius:10px; border:1px solid rgba(255,255,255,.08); margin-bottom:8px; transition:background .2s;" onmouseover="this.style.background='rgba(255,255,255,.08)'" onmouseout="this.style.background='rgba(255,255,255,.05)'">
        <div style="display:flex; align-items:center; gap:12px; min-width:0;">
          <img src="${item.cover}" style="width:40px; height:52px; object-fit:cover; border-radius:6px; flex-shrink:0;" onerror="this.src='https://images.unsplash.com/photo-1578632767115-351597cf2477?w=200&q=80'" />
          <div style="min-width:0;">
            <div style="font-size:13.5px; font-weight:700; color:#e4e6f0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escapeHtml(item.title)}</div>
            <div style="font-size:12px; color:#6c63ff; font-weight:600; margin-top:2px;">${escapeHtml(item.chapter || 'Ch.?')}</div>
          </div>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
          <a href="${item.url || 'detail.html'}" class="btn btn-login" style="padding:6px 14px; font-size:12px; font-weight:700; text-decoration:none;">Đọc tiếp</a>
          <button onclick="removeFromLibrary('${item.id}')" title="Xóa khỏi tủ sách" style="background:none; border:none; color:#ef4444; font-size:15px; cursor:pointer; padding:4px 8px; border-radius:6px; transition:.15s;" onmouseover="this.style.background='rgba(239,68,68,.15)'" onmouseout="this.style.background='none'">🗑️</button>
        </div>
      </div>
    `).join('');
  }

  window.removeFromLibrary = function(comicId) {
    try {
      let savedRaw = JSON.parse(localStorage.getItem('wc_library_comics') || '[]');
      if (!Array.isArray(savedRaw)) savedRaw = [];

      // Xóa đúng cả object lẫn ID string
      savedRaw = savedRaw.filter(item => {
        if (typeof item === 'object' && item !== null) return String(item.id) !== String(comicId);
        return String(item) !== String(comicId);
      });

      localStorage.setItem('wc_library_comics', JSON.stringify(savedRaw));
      renderLibraryItems();

      // Reset nút theo dõi nếu đang xem trang detail
      const btnSub = document.getElementById('btn-subscribe');
      if (btnSub) {
        const urlId = new URLSearchParams(window.location.search).get('id') || '';
        if (String(urlId) === String(comicId) || String(comicId).includes(urlId)) {
          btnSub.dataset.saved = '0';
          btnSub.style.background  = 'transparent';
          btnSub.style.borderColor = 'rgba(255,255,255,.2)';
          btnSub.style.color       = '#e4e6f0';
          const libLabel = document.getElementById('lib-label');
          if (libLabel) libLabel.textContent = '📚 Theo Dõi Truyện';
          const libIcon = document.getElementById('lib-icon');
          if (libIcon) libIcon.setAttribute('fill', 'none');
        }
      }
    } catch(e) { console.error('removeFromLibrary error:', e); }
  };


  // --- 2. SEARCH ---
  const searchInput = document.getElementById('search-input');
  const searchDropdown = document.getElementById('search-dropdown');

  if (searchInput && searchDropdown) {
    // Danh sách tìm kiếm phổ biến mặc định
    const DEFAULT_POPULAR_HTML = `
      <div class="search-recent-title">Popular Searches</div>
      <a href="detail.html?id=1" class="search-item" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:8px;">
        <span>🔥</span>Solo Leveling
      </a>
      <a href="detail.html?id=2" class="search-item" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:8px;">
        <span>⚔️</span>Tower of God
      </a>
      <a href="detail.html?id=3" class="search-item" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:8px;">
        <span>💜</span>Omniscient Reader
      </a>
    `;

    // Hiện dropdown khi focus
    searchInput.addEventListener('focus', () => {
      if (!searchInput.value.trim()) {
        searchDropdown.innerHTML = DEFAULT_POPULAR_HTML;
      }
      searchDropdown.classList.add('visible');
    });

    // Ẩn dropdown khi click ra ngoài
    document.addEventListener('click', (e) => {
      if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
        searchDropdown.classList.remove('visible');
      }
    });

    // Tra cứu trong kho truyện tổng hợp
    function searchCatalog(query) {
      const q = query.toLowerCase();
      const catalog = [
        ...MOCK_TRENDING.map(c => ({ id: c.id, title: c.title, cover: c.cover, genre: c.genre })),
        ...MOCK_LATEST.map(c => ({ id: c.id, title: c.title, cover: c.cover, genre: c.genreText || c.genre })),
        { id: 'detail-eden', title: "Eden's Last Stand", cover: 'https://upload.wikimedia.org/wikipedia/en/6/6c/Solo_Leveling_Volume_1_Cover.jpg', genre: 'Sci-Fi · Action' }
      ];
      
      const seen = new Set();
      const unique = [];
      for (const item of catalog) {
        const key = String(item.id);
        if (!seen.has(key)) {
          seen.add(key);
          unique.push(item);
        }
      }

      return unique.filter(c => 
        c.title.toLowerCase().includes(q) || 
        (c.genre && c.genre.toLowerCase().includes(q))
      );
    }

    function renderSearchResults(results, query) {
      if (results.length > 0) {
        searchDropdown.innerHTML = `
          <div class="search-recent-title">KẾT QUẢ TÌM KIẾM (${results.length})</div>
          ${results.map(item => `
            <a href="${item.id === 'detail-eden' ? 'detail.html' : `detail.html?id=${item.id}`}" class="search-item" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:10px; padding:8px 12px; transition:background .15s;">
              <img src="${item.cover}" style="width:32px; height:42px; object-fit:cover; border-radius:4px; flex-shrink:0;" onerror="this.src='https://images.unsplash.com/photo-1578632767115-351597cf2477?w=100&q=80'" />
              <div style="min-width:0; flex:1;">
                <div style="font-size:13px; font-weight:700; color:#e4e6f0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escapeHtml(item.title)}</div>
                <div style="font-size:11px; color:#94a3b8; font-weight:500;">${escapeHtml(item.genre || 'Manhwa')}</div>
              </div>
            </a>
          `).join('')}
        `;
      } else {
        searchDropdown.innerHTML = `
          <div style="padding:16px 12px; text-align:center; color:#94a3b8; font-size:13px;">
            <div>🔍 Không tìm thấy truyện phù hợp cho "<strong>${escapeHtml(query)}</strong>"</div>
            <div style="font-size:11px; margin-top:4px; color:#64748b;">Hãy thử từ khóa khác như "Solo", "God", "Reader"...</div>
          </div>
        `;
      }
      searchDropdown.classList.add('visible');
    }

    // Xử lý sự kiện nhập từ khóa
    searchInput.addEventListener('input', async (e) => {
      const query = e.target.value.trim();

      if (!query) {
        searchDropdown.innerHTML = DEFAULT_POPULAR_HTML;
        searchDropdown.classList.add('visible');
        return;
      }

      // 1. Thử gọi API trước
      try {
        const res = await fetch(`/api/comics/search?q=${encodeURIComponent(query)}`);
        if (res.ok) {
          const apiResults = await res.json();
          if (Array.isArray(apiResults) && apiResults.length > 0) {
            renderSearchResults(apiResults, query);
            return;
          }
        }
      } catch (err) {
        // API offline -> dùng local catalog
      }

      // 2. Tra cứu local catalog
      const localResults = searchCatalog(query);
      renderSearchResults(localResults, query);
    });

    // Nhấn Enter để xem ngay truyện đầu tiên
    searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        const query = searchInput.value.trim();
        if (!query) return;
        const results = searchCatalog(query);
        if (results.length > 0) {
          const top = results[0];
          window.location.href = top.id === 'detail-eden' ? 'detail.html' : `detail.html?id=${top.id}`;
        }
      }
    });

    // Click vào icon kính lúp để tìm kiếm
    const searchIcon = searchInput.parentElement?.querySelector('.search-icon');
    if (searchIcon) {
      searchIcon.style.cursor = 'pointer';
      searchIcon.style.pointerEvents = 'auto';
      searchIcon.addEventListener('click', () => {
        const query = searchInput.value.trim();
        if (!query) {
          searchInput.focus();
          return;
        }
        const results = searchCatalog(query);
        if (results.length > 0) {
          const top = results[0];
          window.location.href = top.id === 'detail-eden' ? 'detail.html' : `detail.html?id=${top.id}`;
        }
      });
    }
  }


  // --- 3. SCHEDULE PAGE INTERACTION (SCHEDULE.HTML) ---
  const dayItems = document.querySelectorAll('.sched-day-item');
  const browseGrid = document.querySelector('.browse-grid');

  if (dayItems.length > 0 && browseGrid) {
    
    function renderScheduleForDay(dayKey) {
      const dayData = SCHEDULE_DATA[dayKey] || SCHEDULE_DATA['thursday'];
      
      browseGrid.innerHTML = dayData.map(item => `
        <div class="browse-card" onclick="window.location.href='detail.html?id=${item.id}'" style="cursor:pointer;">
          <div class="browse-cover">
            <img src="${item.cover}" alt="${escapeHtml(item.title)}" class="cover-img" />
            <span class="badge-tag hot">NEW ${item.chapter}</span>
            <span class="rating-tag">★ 9.8</span>
          </div>
          <div class="browse-info">
            <h3 class="browse-title"><a href="detail.html?id=${item.id}" style="color:inherit; text-decoration:none;">${escapeHtml(item.title)}</a></h3>
            <p class="browse-author">${escapeHtml(item.author)}</p>
            <p class="browse-meta"><span>${escapeHtml(item.genre)}</span> &middot; <span>${item.chapter} Released</span></p>
            <p class="browse-desc">${escapeHtml(item.desc)}</p>
          </div>
        </div>
      `).join('');

      attachImageFallback();
    }

    dayItems.forEach(item => {
      item.addEventListener('click', (e) => {
        e.preventDefault();
        dayItems.forEach(i => i.classList.remove('active'));
        item.classList.add('active');

        const dayKey = item.getAttribute('data-day') || 'thursday';
        const dayName = item.querySelector('.day-name')?.textContent || 'THU';
        
        const currentTitleElem = document.querySelector('.sched-current-title h2');
        if (currentTitleElem) {
          const fullDays = {
            'MON': 'Monday', 'TUE': 'Tuesday', 'WED': 'Wednesday',
            'THU': 'Thursday', 'FRI': 'Friday', 'SAT': 'Saturday', 'SUN': 'Sunday'
          };
          currentTitleElem.textContent = `${fullDays[dayName] || dayName} Releases`;
        }

        renderScheduleForDay(dayKey);
      });
    });

    // Make initial cards in schedule page clickable
    document.querySelectorAll('.browse-card').forEach((card, index) => {
      card.style.cursor = 'pointer';
      card.addEventListener('click', (e) => {
        if (e.target.tagName && e.target.tagName.toLowerCase() === 'a') return;
        window.location.href = `detail.html?id=${(index % 9) + 1}`;
      });
    });
  }


  // --- 4. ORIGINALS PAGE INTERACTION (ORIGINALS.HTML) ---
  const origTabs = document.querySelectorAll('#orig-tabs .genre-tab');
  const origFullCards = document.querySelectorAll('.orig-full-card');

  if (origTabs.length > 0) {
    origTabs.forEach(tab => {
      tab.addEventListener('click', () => {
        origTabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        const selectedCategory = tab.textContent.trim().toLowerCase();

        origFullCards.forEach((card, idx) => {
          const cardContent = card.textContent.toLowerCase();
          if (selectedCategory.includes("editor") || selectedCategory.includes("pick") || cardContent.includes(selectedCategory)) {
            card.style.display = 'flex';
          } else {
            // Cho hiển thị ngẫu nhiên 60% card để mô phỏng kết quả lọc
            card.style.display = (idx % 2 === 0) ? 'flex' : 'none';
          }
        });
      });
    });
  }

  // Make all .orig-full-card clickable to detail.html
  if (origFullCards.length > 0) {
    origFullCards.forEach((card, index) => {
      card.style.cursor = 'pointer';
      card.addEventListener('click', (e) => {
        if (e.target.tagName && e.target.tagName.toLowerCase() === 'a') return;
        window.location.href = `detail.html?id=${(index % 9) + 1}`;
      });
    });
  }

  // Spotlight Banner Actions
  const btnSpotlightRead = document.querySelector('.btn-spotlight-read');
  const btnSpotlightSub = document.querySelector('.btn-spotlight-sub');

  if (btnSpotlightRead) {
    btnSpotlightRead.addEventListener('click', () => {
      window.location.href = 'detail.html?id=1';
    });
  }

  if (btnSpotlightSub) {
    btnSpotlightSub.addEventListener('click', () => {
      alert('✅ Đã thêm "Solo Leveling" vào Thư viện đọc truyện của bạn!');
    });
  }


  // --- 5. GENRES PAGE INTERACTION (GENRES.HTML) ---
  const filterChips = document.querySelectorAll('.filter-panel .chip');
  if (filterChips.length > 0) {
    filterChips.forEach(chip => {
      chip.addEventListener('click', () => {
        const parentChipsGroup = chip.closest('.filter-chips');
        if (parentChipsGroup) {
          parentChipsGroup.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
          chip.classList.add('active');
        }

        const selectedVal = chip.textContent.trim().toLowerCase();
        const cards = document.querySelectorAll('.browse-grid .browse-card');

        cards.forEach((card, idx) => {
          const cardContent = card.textContent.toLowerCase();
          if (selectedVal.includes('all') || selectedVal.includes('hottest') || cardContent.includes(selectedVal)) {
            card.style.display = 'flex';
          } else {
            card.style.display = (idx % 2 === 0) ? 'flex' : 'none';
          }
        });
      });
    });
  }

  // Make all .browse-card in genres.html clickable
  document.querySelectorAll('.browse-card').forEach((card, index) => {
    card.style.cursor = 'pointer';
    card.addEventListener('click', (e) => {
      if (e.target.tagName && e.target.tagName.toLowerCase() === 'a') return;
      window.location.href = `detail.html?id=${(index % 9) + 1}`;
    });
  });


  // --- 6. HOME PAGE & DETAIL PAGE ---
  function renderTrendingComics(comics) {
    const trendContainer = document.getElementById('trending-list');
    if (!trendContainer) return;

    trendContainer.innerHTML = comics.map(item => `
      <a href="detail.html?id=${item.id}" class="trending-card" aria-label="${escapeHtml(item.title)}">
        <div class="tcard-cover">
          <img src="${item.cover}" alt="${escapeHtml(item.title)} Cover" class="cover-img" />
          <div class="rank-num ${item.rankClass || ''}">${item.rank}</div>
        </div>
        <p class="tcard-title">${escapeHtml(item.title)}</p>
        <p class="tcard-genre">${escapeHtml(item.genre)}</p>
      </a>
    `).join('');

    attachImageFallback();
  }

  function renderLatestComics(comics) {
    const gridContainer = document.getElementById('new-updates-grid');
    if (!gridContainer) return;

    gridContainer.innerHTML = comics.map(item => `
      <a href="detail.html?id=${item.id}" class="comic-card-sm" data-genre="${item.genre}">
        <div class="sm-cover">
          <img src="${item.cover}" alt="${escapeHtml(item.title)}" class="cover-img" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1578632767115-351597cf2477?w=600&q=80'" />
          <span class="sm-badge ${item.badgeClass || ''}">${escapeHtml(item.chapter)}</span>
          <span class="sm-rating">★ ${item.rating || '4.9'}</span>
        </div>
        <div class="sm-info">
          <h3 class="sm-title">${escapeHtml(item.title)}</h3>
          <p class="sm-meta"><span style="color:var(--primary); font-weight:600;">${escapeHtml((item.genreText || item.genre || '').split(' · ')[0])}</span> <span>${escapeHtml(item.timeAgo || 'Hot')}</span></p>
        </div>
      </a>
    `).join('');

    attachImageFallback();
  }


  async function loadTrendingComics() {
    try {
      const response = await fetch(`${API_BASE_URL}/comics/trending`);
      if (!response.ok) throw new Error('API Error');
      const data = await response.json();
      renderTrendingComics(data);
    } catch (err) {
      renderTrendingComics(MOCK_TRENDING);
    }
  }

  async function loadLatestComics(genre = 'all') {
    try {
      const url = genre === 'all'
        ? `${API_BASE_URL}/comics/latest`
        : `${API_BASE_URL}/comics/latest?genre=${encodeURIComponent(genre)}`;

      const response = await fetch(url);
      if (!response.ok) throw new Error('API Error');
      const data = await response.json();
      renderLatestComics(data);
    } catch (err) {
      const filtered = genre === 'all'
        ? MOCK_LATEST
        : MOCK_LATEST.filter(c => c.genre.toLowerCase() === genre.toLowerCase());
      renderLatestComics(filtered);
    }
  }

  if (document.getElementById('trending-list')) loadTrendingComics();
  if (document.getElementById('new-updates-grid')) loadLatestComics('all');

  // Detail Page Logic
  const detailMain = document.getElementById('comic-detail-main');
  if (detailMain) {
    const urlParams = new URLSearchParams(window.location.search);
    const comicId = urlParams.get('id') || 1;

    async function loadComicDetail() {
      try {
        const response = await fetch(`${API_BASE_URL}/comics/detail?id=${comicId}`);
        if (!response.ok) throw new Error('API Error');
        const data = await response.json();
        populateComicDetail(data);
      } catch (err) {
        const mockDetail = {
          id: comicId,
          title: 'Solo Leveling',
          author: 'Chugong & DUBU (REDICE STUDIO)',
          status: 'ONGOING',
          rating: '4.9',
          views: '15.8M',
          likes: '920K',
          tags: ['Action', 'Fantasy', 'Supernatural'],
          cover: 'https://upload.wikimedia.org/wikipedia/en/6/6c/Solo_Leveling_Volume_1_Cover.jpg',
          description: 'In a world where hunters, humans who possess magical abilities, must battle deadly monsters to protect the human race from certain annihilation, a notoriously weak hunter named Sung Jinwoo finds himself in a seamless struggle for survival...',
          totalChapters: 200,
          chapters: Array.from({ length: 20 }, (_, i) => ({
            id: 200 - i,
            number: `Chapter ${200 - i}`,
            title: `The Awakening - Part ${200 - i}`,
            date: `2024-07-20`,
            views: `${(200 - i) * 15.2}K`
          }))
        };
        populateComicDetail(mockDetail);
      }
    }

    function populateComicDetail(data) {
      document.title = `${data.title} - WebComics`;
      document.getElementById('detail-title').textContent = data.title;
      document.getElementById('detail-author').innerHTML = `By <span class="author-name">${escapeHtml(data.author)}</span>`;
      document.getElementById('detail-cover-img').src = data.cover;
      document.getElementById('detail-backdrop-bg').style.backgroundImage = `url('${data.cover}')`;
      document.getElementById('detail-status').textContent = data.status || 'ONGOING';
      document.getElementById('detail-rating').textContent = `⭐ ${data.rating}`;
      document.getElementById('detail-views').textContent = data.views;
      document.getElementById('detail-likes').textContent = data.likes;
      document.getElementById('detail-chapters-count').textContent = data.totalChapters || data.chapters.length;
      document.getElementById('episodes-tab-count').textContent = data.totalChapters || data.chapters.length;
      document.getElementById('detail-description').textContent = data.description;

      const btnReadLatest = document.getElementById('btn-read-latest');
      if (btnReadLatest && data.totalChapters) {
        btnReadLatest.textContent = `Read Ch.${data.totalChapters}`;
      }

      const tagsContainer = document.getElementById('detail-tags-list');
      if (tagsContainer && data.tags) {
        tagsContainer.innerHTML = data.tags.map(t => `<span class="detail-tag">${escapeHtml(t)}</span>`).join('');
      }

      renderChaptersList(data.chapters);
      attachImageFallback();
    }

    function renderChaptersList(chapters) {
      const epListContainer = document.getElementById('episodes-list');
      if (!epListContainer) return;

      epListContainer.innerHTML = chapters.map(ep => `
        <a href="#" class="episode-card-item" onclick="alert('Đang mở đọc ${escapeHtml(ep.number)}'); return false;">
          <div class="ep-left">
            <span class="ep-number">${escapeHtml(ep.number)}</span>
            <span class="ep-title">${escapeHtml(ep.title)}</span>
          </div>
          <div class="ep-right">
            <span class="ep-date">${escapeHtml(ep.date)}</span>
            <span class="ep-read-badge">Read</span>
          </div>
        </a>
      `).join('');
    }

    loadComicDetail();
  }

  // Genre Tabs on Home Page
  const genreTabs = document.querySelectorAll('.genre-tab');
  genreTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      if (tab.closest('#orig-tabs')) return; // Ignore if inside originals tab
      genreTabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      const selectedGenre = tab.getAttribute('data-genre');
      loadLatestComics(selectedGenre);
    });
  });

  // Scroll Left/Right
  const trendList = document.getElementById('trending-list');
  const trendLeft = document.getElementById('trend-left');
  const trendRight = document.getElementById('trend-right');

  if (trendLeft && trendRight && trendList) {
    trendLeft.addEventListener('click', () => trendList.scrollBy({ left: -320, behavior: 'smooth' }));
    trendRight.addEventListener('click', () => trendList.scrollBy({ left: 320, behavior: 'smooth' }));
  }

  // Image Fallback
  const fallbacks = [
    'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=600&auto=format&fit=crop&q=80',
    'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=600&auto=format&fit=crop&q=80',
    'https://images.unsplash.com/photo-1563089145-599997674d42?w=600&auto=format&fit=crop&q=80'
  ];

  function attachImageFallback() {
    document.querySelectorAll('img.cover-img').forEach((img, idx) => {
      img.onerror = function() {
        const fallbackUrl = fallbacks[idx % fallbacks.length];
        if (this.src !== fallbackUrl) this.src = fallbackUrl;
      };
    });
  }

  attachImageFallback();

  function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
  }
});
