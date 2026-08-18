const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');

const PORT = 3000;

// Dữ liệu Mock API 35+ bộ truyện chuẩn đa dạng thể loại
const trendingComics = [
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

const latestComics = [
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
  { id: 35, title: 'The Greatest Estate Developer', genre: 'comedy', genreText: 'Isekai · Comedy', author: 'BK_Moon', rating: '4.9', views: '12.1M', likes: '940K', status: 'ONGOING', timeAgo: '2h ago', chapter: 'Ch.150', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=600&auto=format&fit=crop&q=80' },
  { id: 36, title: 'Cyberpunk: Edgerunners', genre: 'sci-fi', genreText: 'Sci-Fi · Cyberpunk', author: 'Studio Trigger', rating: '4.9', views: '9.4M', likes: '710K', status: 'COMPLETED', timeAgo: '1d ago', chapter: 'Ch.24', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=600&q=80' },
  { id: 37, title: 'Dr. STONE', genre: 'sci-fi', genreText: 'Sci-Fi · Adventure', author: 'Riichiro Inagaki', rating: '4.8', views: '11.5M', likes: '820K', status: 'COMPLETED', timeAgo: '2d ago', chapter: 'Ch.232', badgeClass: '', cover: 'https://images.unsplash.com/photo-1507413245164-6160d8298b31?w=600&q=80' },
  { id: 38, title: 'Monster', genre: 'mystery', genreText: 'Mystery · Thriller', author: 'Naoki Urasawa', rating: '4.9', views: '8.6M', likes: '690K', status: 'COMPLETED', timeAgo: '3d ago', chapter: 'Ch.162', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=600&q=80' },
  { id: 39, title: 'Death Note', genre: 'thriller', genreText: 'Psychological · Thriller', author: 'Tsugumi Ohba', rating: '4.9', views: '25.1M', likes: '1.8M', status: 'COMPLETED', timeAgo: '4d ago', chapter: 'Ch.108', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=600&q=80' },
  { id: 40, title: 'Tokyo Ghoul', genre: 'horror', genreText: 'Horror · Supernatural', author: 'Sui Ishida', rating: '4.8', views: '17.3M', likes: '1.1M', status: 'COMPLETED', timeAgo: '1d ago', chapter: 'Ch.143', badgeClass: '', cover: 'https://images.unsplash.com/photo-1509248961158-e54f6934749c?w=600&q=80' },
  { id: 41, title: 'Haikyuu!!', genre: 'sports', genreText: 'Sports · School Life', author: 'Haruichi Furudate', rating: '4.9', views: '19.4M', likes: '1.3M', status: 'COMPLETED', timeAgo: '5h ago', chapter: 'Ch.402', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1517649763962-0c623266010b?w=600&q=80' },
  { id: 42, title: 'Kaguya-sama: Love Is War', genre: 'romance', genreText: 'Romance · Comedy', author: 'Aka Akasaka', rating: '4.9', views: '16.7M', likes: '1.0M', status: 'COMPLETED', timeAgo: '3h ago', chapter: 'Ch.281', badgeClass: 'new-badge', cover: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=600&q=80' },
  { id: 43, title: 'Horimiya', genre: 'romance', genreText: 'Slice of Life · Romance', author: 'HERO & Daisuke Hagiwara', rating: '4.8', views: '10.2M', likes: '740K', status: 'COMPLETED', timeAgo: '6h ago', chapter: 'Ch.125', badgeClass: '', cover: 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=600&q=80' },
  { id: 44, title: 'Blue Lock', genre: 'sports', genreText: 'Sports · Psychological', author: 'Muneyuki Kaneshiro', rating: '4.9', views: '14.8M', likes: '960K', status: 'ONGOING', timeAgo: '1h ago', chapter: 'Ch.270', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1485965120184-e220f721d03e?w=600&q=80' },
  { id: 45, title: 'Vinland Saga', genre: 'historical', genreText: 'Historical · Action', author: 'Makoto Yukimura', rating: '4.9', views: '13.2M', likes: '890K', status: 'ONGOING', timeAgo: '8h ago', chapter: 'Ch.210', badgeClass: '', cover: 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=600&q=80' },
  { id: 46, title: 'Kingdom', genre: 'historical', genreText: 'Historical · Military', author: 'Yasuhisa Hara', rating: '4.9', views: '18.1M', likes: '1.2M', status: 'ONGOING', timeAgo: '2h ago', chapter: 'Ch.800', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=600&q=80' },
  { id: 47, title: 'Frieren: Beyond Journey\'s End', genre: 'fantasy', genreText: 'Fantasy · Slice of Life', author: 'Kanehito Yamada', rating: '4.9', views: '15.9M', likes: '1.1M', status: 'ONGOING', timeAgo: '4h ago', chapter: 'Ch.130', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=600&q=80' },
  { id: 48, title: 'Mushoku Tensei: Jobless Reincarnation', genre: 'isekai', genreText: 'Isekai · Magic', author: 'Rifujin na Magonote', rating: '4.8', views: '12.6M', likes: '870K', status: 'ONGOING', timeAgo: '7h ago', chapter: 'Ch.100', badgeClass: 'new-badge', cover: 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=600&q=80' },
  { id: 49, title: 'AOT: Before the Fall', genre: 'action', genreText: 'Action · Dystopian', author: 'Ryo Suzukaze', rating: '4.6', views: '4.5M', likes: '310K', status: 'COMPLETED', timeAgo: '2d ago', chapter: 'Ch.65', badgeClass: '', cover: 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=600&q=80' },
  { id: 50, title: 'All You Need Is Kill', genre: 'sci-fi', genreText: 'Sci-Fi · Time Travel', author: 'Hiroshi Sakurazaka', rating: '4.8', views: '5.3M', likes: '400K', status: 'COMPLETED', timeAgo: '3d ago', chapter: 'Ch.17', badgeClass: 'new-badge', cover: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600&q=80' },
  { id: 51, title: 'Omniscient First Person Perspective', genre: 'fantasy', genreText: 'System · Fantasy', author: 'Nolto', rating: '4.8', views: '6.9M', likes: '480K', status: 'ONGOING', timeAgo: '5h ago', chapter: 'Ch.88', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1563089145-599997674d42?w=600&q=80' },
  { id: 52, title: 'The World After the Fall', genre: 'action', genreText: 'Apocalypse · Action', author: 'singNsong', rating: '4.8', views: '9.7M', likes: '710K', status: 'ONGOING', timeAgo: '3h ago', chapter: 'Ch.120', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=600&q=80' },
  { id: 53, title: 'Pick Me Up! Infinite Gacha', genre: 'fantasy', genreText: 'Game · Fantasy', author: 'Hermod', rating: '4.9', views: '11.1M', likes: '830K', status: 'ONGOING', timeAgo: '2h ago', chapter: 'Ch.95', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1538481199705-c710c4e965fc?w=600&q=80' },
  { id: 54, title: 'Mercenary of Seoul', genre: 'action', genreText: 'Action · Modern', author: 'Kim Tae-jin', rating: '4.7', views: '5.1M', likes: '360K', status: 'ONGOING', timeAgo: '9h ago', chapter: 'Ch.75', badgeClass: '', cover: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&q=80' },
  { id: 55, title: 'Medical Return', genre: 'drama', genreText: 'Medical · Reincarnation', author: 'Kidari Studio', rating: '4.8', views: '8.3M', likes: '620K', status: 'COMPLETED', timeAgo: '1d ago', chapter: 'Ch.148', badgeClass: '', cover: 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=600&q=80' },
  { id: 56, title: 'Bocchi the Rock!', genre: 'comedy', genreText: 'Music · Comedy', author: 'Aki Hamazi', rating: '4.9', views: '7.4M', likes: '580K', status: 'ONGOING', timeAgo: '12h ago', chapter: 'Ch.70', badgeClass: 'new-badge', cover: 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=600&q=80' },
  { id: 57, title: 'Food Wars!: Shokugeki no Soma', genre: 'comedy', genreText: 'Cooking · School Life', author: 'Yuto Tsukuda', rating: '4.7', views: '13.9M', likes: '910K', status: 'COMPLETED', timeAgo: '2d ago', chapter: 'Ch.315', badgeClass: '', cover: 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&q=80' },
  { id: 58, title: 'Call of the Night', genre: 'supernatural', genreText: 'Vampire · Supernatural', author: 'Kotoyama', rating: '4.8', views: '9.1M', likes: '670K', status: 'COMPLETED', timeAgo: '1d ago', chapter: 'Ch.200', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=600&q=80' },
  { id: 59, title: '86 -Eighty Six-', genre: 'sci-fi', genreText: 'Mecha · Sci-Fi · Drama', author: 'Asato Asato', rating: '4.9', views: '8.8M', likes: '650K', status: 'ONGOING', timeAgo: '6h ago', chapter: 'Ch.50', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1563089145-599997674d42?w=600&q=80' },
  { id: 60, title: 'Code Geass: Lelouch of the Rebellion', genre: 'sci-fi', genreText: 'Mecha · Psychological', author: 'CLAMP & Sunrise', rating: '4.9', views: '21.4M', likes: '1.6M', status: 'COMPLETED', timeAgo: '3d ago', chapter: 'Ch.50', badgeClass: 'hot-badge', cover: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600&q=80' }
];

// Hàm tạo thông tin chi tiết bộ truyện khớp chính xác với số chapter
function getComicDetail(id) {
  const base = latestComics.find(c => String(c.id) === String(id)) || trendingComics.find(c => String(c.id) === String(id)) || latestComics[0];
  
  const latestChapterNum = parseInt((base.chapter || '200').replace(/\D/g, ''), 10) || 200;
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
    author: base.author || 'Chugong & DUBU (REDICE STUDIO)',
    status: base.status || 'ONGOING',
    rating: base.rating || '4.9',
    views: base.views || '15.8M',
    likes: base.likes || '920K',
    tags: [base.genreText ? base.genreText.split(' · ')[0] : 'Action', 'Fantasy', 'Supernatural', 'Webtoon'],
    cover: base.cover,
    description: `Series cực hot "${base.title}". Trong một thế giới thần thoại đầy kỳ bí, các anh hùng cùng hội tụ để vượt qua thử thách nghiệt ngã và bảo vệ hòa bình nhân loại...`,
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
