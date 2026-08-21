@extends('layouts.main')

@section('title', $comic->title . ' - WebComics')

@section('meta')
<meta name="description" content="{{ Str::limit($comic->description, 160) }}" />
@endsection

@push('styles')
<style>
  .detail-tab-pane{display:none}.detail-tab-pane.active{display:block}.detail-nav-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:30px 0 18px}.dtab-btn{border:1px solid var(--border);background:rgba(255,255,255,.04);color:var(--text-sub);padding:10px 16px;border-radius:10px;font-weight:800;cursor:pointer}.dtab-btn.active{background:rgba(108,99,255,.16);border-color:var(--primary);color:var(--primary)}
  .detail-comments-card,.detail-rating-card{background:var(--card-bg);border:1px solid var(--border);border-radius:14px;padding:18px}.detail-comment{display:flex;gap:12px;padding:15px 0;border-bottom:1px solid var(--border)}.detail-comment.reply{margin-left:48px}.detail-avatar{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#6c63ff,#ff2a6d);font-weight:900;color:#fff;flex:0 0 auto}.detail-comment-body{min-width:0;flex:1}.detail-comment-head{display:flex;align-items:center;gap:8px;flex-wrap:wrap}.detail-comment-text{line-height:1.6;margin:7px 0 10px;white-space:pre-wrap;word-break:break-word}.comment-action{border:0;background:transparent;color:var(--text-sub);cursor:pointer;font-weight:700;font-size:12px;padding:0}.comment-action.liked{color:#ef4444}.reply-editor{display:none;margin-top:10px}.reply-editor.open{display:flex;gap:8px}.reply-editor textarea,.detail-comment-input{width:100%;background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:10px;color:var(--text-main);padding:11px 12px;font-family:inherit;resize:vertical}.comment-tabs{display:flex;gap:7px;flex-wrap:wrap}.comment-sort{padding:6px 12px;border-radius:999px;border:1px solid var(--border);background:rgba(255,255,255,.04);color:var(--text-sub);cursor:pointer;font-size:12px;font-weight:800}.comment-sort.active{background:var(--primary);color:#fff;border-color:var(--primary)}
  .rating-grid{display:grid;grid-template-columns:220px minmax(0,1fr);gap:24px}.rating-score-num{font-size:44px;font-weight:900}.rating-stars-display{color:#f59e0b;font-size:24px;letter-spacing:2px}.rating-bar-row{display:grid;grid-template-columns:42px 1fr 45px;gap:8px;align-items:center;margin:6px 0;font-size:12px}.rating-bar-track{height:7px;border-radius:99px;background:rgba(255,255,255,.07);overflow:hidden}.rating-bar-fill{height:100%;background:#f59e0b}.star-rating-select{display:flex;gap:4px;margin:10px 0}.star-btn{font-size:30px;background:transparent;border:0;color:#555;cursor:pointer}.star-btn.active,.star-btn.hovered{color:#f59e0b}.rating-review-textarea{width:100%;min-height:90px;background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:10px;color:var(--text-main);padding:11px 12px;font-family:inherit;resize:vertical;margin-bottom:10px}.review-item{padding:12px 0;border-bottom:1px solid var(--border)}.review-item-header{display:flex;justify-content:space-between;gap:10px}.review-badge{color:#f59e0b;font-weight:900}
  @media(max-width:700px){.rating-grid{grid-template-columns:1fr}.detail-comment.reply{margin-left:18px}}
</style>
@endpush

@section('content')
<main class="page-container">
  <div class="container">
    <div class="page-header"><div class="breadcrumb"><a href="{{ route('home') }}">Trang Chủ</a> &rsaquo; <a href="{{ route('genres') }}">Truyện</a> &rsaquo; <span>{{ $comic->title }}</span></div></div>

    <section class="orig-spotlight-card" style="margin-bottom:30px;">
      <div class="spotlight-cover">
        <img src="{{ $comic->cover_image }}" alt="{{ $comic->title }}" class="cover-img" />
        @if($comic->is_original)<span class="spotlight-badge">ORIGINAL</span>@endif
      </div>
      <div class="spotlight-details">
        <div class="spotlight-tags">
          @foreach($comic->genres as $genre)<a href="{{ route('genres',['genre'=>$genre->slug]) }}" class="genre-tag">{{ $genre->name }}</a>@endforeach
          @foreach($comic->tags as $tag)<span class="orig-tag">{{ $tag->name }}</span>@endforeach
        </div>
        <h1 class="spotlight-title">{{ $comic->title }}</h1>
        <p class="spotlight-author">Tác giả: {{ $comic->authors->pluck('name')->join(' · ') ?: 'Chưa cập nhật' }} · ⭐ {{ number_format($comic->avg_rating,1) }} · 👁 {{ $comic->formatted_views }} · {{ ucfirst($comic->status) }}</p>

        @php
          $likeCount = $comic->likes()->count();
          $isLiked = auth()->check() ? $comic->hasLikedBy(auth()->id()) : false;
          $isSaved = auth()->check() ? auth()->user()->hasInLibrary($comic->id) : false;
          $lastHistory = auth()->check() ? auth()->user()->readingHistoryForComic($comic->id) : null;
          $lastChapter = $lastHistory?->chapter;
          $firstChapter = $comic->chapters->last();
          $latestChapter = $comic->chapters->first();
        @endphp

        <div style="display:flex;gap:18px;flex-wrap:wrap;margin:10px 0 14px;color:var(--text-sub);font-size:13px;">
          <span>❤️ <strong id="like-count">{{ number_format($likeCount) }}</strong> lượt thích</span>
          <span>📖 <strong>{{ number_format($comic->chapters_count) }}</strong> chương</span>
          <span>⭐ <strong>{{ number_format($comic->total_ratings) }}</strong> đánh giá</span>
        </div>
        <p class="spotlight-desc">{{ $comic->description }}</p>

        <div class="spotlight-actions" style="display:flex;flex-wrap:wrap;gap:10px;margin-top:16px;">
          @if($lastChapter)
            <a href="{{ route('chapters.show',[$comic->slug,$lastChapter->slug]) }}" class="btn-spotlight-read">📖 Đọc Tiếp Ch.{{ $lastChapter->chapter_number }}{{ ($lastHistory->scroll_percent ?? 0)>0 ? ' · '.round($lastHistory->scroll_percent).'%' : '' }}</a>
          @elseif($firstChapter)
            <a href="{{ route('chapters.show',[$comic->slug,$firstChapter->slug]) }}" class="btn-spotlight-read">🚀 Đọc Từ Chương {{ $firstChapter->chapter_number }}</a>
          @endif
          @if($latestChapter && (!$lastChapter || $latestChapter->id !== $lastChapter->id))<a href="{{ route('chapters.show',[$comic->slug,$latestChapter->slug]) }}" class="btn-spotlight-sub" style="text-decoration:none;">Chương Mới Nhất {{ $latestChapter->chapter_number }}</a>@endif

          @auth
          <button type="button" id="btn-toggle-library" data-comic="{{ $comic->id }}" data-saved="{{ $isSaved ? '1':'0' }}" class="btn-spotlight-sub" style="cursor:pointer;{{ $isSaved?'background:#16a34a;color:#fff;border-color:#16a34a;':'' }}"><span id="lib-label">{{ $isSaved?'✓ Đã Theo Dõi':'📚 Theo Dõi Truyện' }}</span></button>
          <button type="button" id="btn-toggle-like" data-comic="{{ $comic->id }}" data-liked="{{ $isLiked ? '1':'0' }}" class="btn-spotlight-sub" style="cursor:pointer;{{ $isLiked?'background:#ef4444;color:#fff;border-color:#ef4444;':'' }}"><span id="like-label">{{ $isLiked?'❤️ Đã Thích':'🤍 Yêu Thích' }}</span></button>
          @else
          <a href="{{ route('login') }}" class="btn-spotlight-sub" style="text-decoration:none;">📚 Theo Dõi Truyện</a><a href="{{ route('login') }}" class="btn-spotlight-sub" style="text-decoration:none;">🤍 Yêu Thích</a>
          @endauth
        </div>
        <div id="action-toast" style="display:none;margin-top:12px;padding:10px 14px;border-radius:9px;font-size:13px;font-weight:700;"></div>
      </div>
    </section>

    <div class="detail-nav-tabs">
      <button class="dtab-btn active" data-detail-tab="chapters">📖 Danh Sách Chương ({{ $comic->chapters_count }})</button>
      <button class="dtab-btn" data-detail-tab="community">💬 Bình Luận & Đánh Giá</button>
    </div>

    <section id="detail-tab-chapters" class="detail-tab-pane active">
      <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:12px;"><h2 class="section-title" style="margin:0;">Danh sách chapter</h2><div style="display:flex;gap:6px;"><button type="button" class="comment-sort active" id="chap-sort-desc">Mới nhất</button><button type="button" class="comment-sort" id="chap-sort-asc">Cũ nhất</button></div></div>
      <div id="chapter-list" style="display:flex;flex-direction:column;gap:8px;">
        @forelse($comic->chapters as $chapter)
          <a href="{{ route('chapters.show',[$comic->slug,$chapter->slug]) }}" class="browse-card chapter-row" data-chapter="{{ $chapter->chapter_number }}" style="padding:16px 20px;text-decoration:none;align-items:center;">
            <div class="browse-info" style="padding:0;"><h3 class="browse-title" style="font-size:15px;">Chương {{ $chapter->chapter_number }} @if($chapter->title)— {{ $chapter->title }}@endif</h3><p class="browse-meta" style="margin:4px 0 0;">{{ $chapter->time_ago }} @if(!$chapter->is_free)· 🔒 Premium@endif</p></div>
          </a>
        @empty
          <div style="padding:35px;text-align:center;border:1px dashed var(--border);border-radius:12px;color:var(--text-sub);">Chưa có chương nào được phát hành.</div>
        @endforelse
      </div>
    </section>

    <section id="detail-tab-community" class="detail-tab-pane">
      <div class="detail-comments-card" style="margin-bottom:18px;">
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px;"><h2 style="margin:0;font-size:19px;">💬 Cộng Đồng Thảo Luận</h2><div class="comment-tabs"><button class="comment-sort active" data-comment-sort="newest">🆕 Mới nhất</button><button class="comment-sort" data-comment-sort="top">🔥 Nổi bật</button></div></div>
        @auth
          <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:16px;"><div class="detail-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name,0,1)) }}</div><div style="flex:1;"><textarea id="detail-comment-input" class="detail-comment-input" rows="3" maxlength="2000" placeholder="Chia sẻ cảm nhận của bạn về bộ truyện này..."></textarea><div style="display:flex;justify-content:space-between;gap:8px;align-items:center;margin-top:8px;"><span style="font-size:11px;color:var(--text-sub);">Ctrl + Enter để gửi · Link/số điện thoại có thể bị đưa vào hàng chờ duyệt.</span><button type="button" id="btn-post-detail-comment" class="btn-spotlight-read" style="padding:8px 15px;">➤ Đăng bình luận</button></div></div></div>
        @else
          <div style="padding:15px;border:1px solid var(--border);border-radius:10px;margin-bottom:15px;color:var(--text-sub);">M có thể xem bình luận mà không cần tài khoản. <a href="{{ route('login') }}">Đăng nhập</a> để bình luận, trả lời và thích bình luận.</div>
        @endauth
        <div id="detail-comments-list"><p style="color:var(--text-sub);">Đang tải bình luận...</p></div>
        <div style="text-align:center;margin-top:14px;"><button type="button" id="btn-more-comments" class="btn-spotlight-sub" style="display:none;cursor:pointer;">Xem thêm bình luận</button></div>
      </div>

      <div class="detail-rating-card">
        <div class="section-header" style="margin-bottom:18px;"><h2 class="section-title">⭐ Đánh Giá & Nhận Xét</h2></div>
        <div class="rating-grid">
          <div><div class="rating-score-num" id="rating-avg-display">{{ number_format($comic->avg_rating,1) }}</div><div class="rating-stars-display" id="rating-stars-icons"></div><div style="font-size:12px;color:var(--text-sub);">Dựa trên <strong id="rating-total-display">{{ $comic->total_ratings }}</strong> lượt đánh giá</div></div>
          <div>@for($s=5;$s>=1;$s--)<div class="rating-bar-row"><span>{{ $s }} ★</span><div class="rating-bar-track"><div class="rating-bar-fill" id="bar-fill-{{ $s }}" style="width:0%"></div></div><span id="bar-percent-{{ $s }}">0%</span></div>@endfor</div>
        </div>

        @auth
        <div style="border-top:1px solid var(--border);padding-top:18px;margin-top:18px;"><h3 style="font-size:15px;margin:0;">Đánh giá của bạn</h3><div class="star-rating-select" id="star-selector">@for($i=1;$i<=5;$i++)<button type="button" class="star-btn" data-value="{{ $i }}">★</button>@endfor</div><input type="hidden" id="selected-score" value="0"><textarea id="rating-review-input" class="rating-review-textarea" maxlength="1000" placeholder="Viết cảm nhận (tùy chọn)..."></textarea><div style="display:flex;gap:8px;"><button type="button" id="btn-submit-rating" class="btn-spotlight-read" style="padding:8px 16px;">Gửi Đánh Giá</button><button type="button" id="btn-delete-rating" class="btn-spotlight-sub" style="display:none;color:#ef4444;padding:8px 14px;">Xóa Đánh Giá</button></div></div>
        @else
        <div style="border-top:1px solid var(--border);padding-top:18px;margin-top:18px;color:var(--text-sub);"><a href="{{ route('login') }}">Đăng nhập</a> để gửi đánh giá.</div>
        @endauth

        <div id="reviews-list-container" style="margin-top:22px;"><h3 style="font-size:15px;">Nhận xét gần đây</h3><div id="reviews-items"><p style="color:var(--text-sub);">Đang tải nhận xét...</p></div></div>
      </div>
    </section>

    @php($suggested = ($recommendations ?? collect())->filter(fn($item) => $item->id !== $comic->id)->take(6))
    @if($suggested->isNotEmpty())
    <section class="comics-section" style="padding-top:42px;"><div class="section-header"><h2 class="section-title">✨ Dành Cho Bạn</h2></div><div class="comics-grid">@foreach($suggested as $item)<a href="{{ route('comics.show',$item->slug) }}" class="comic-card-sm"><div class="sm-cover"><img src="{{ $item->cover_image }}" alt="{{ $item->title }}" class="cover-img" loading="lazy"><span class="sm-badge">★ {{ number_format($item->avg_rating,1) }}</span></div><p class="sm-title">{{ $item->title }}</p></a>@endforeach</div></section>
    @elseif($relatedComics->isNotEmpty())
    <section class="comics-section" style="padding-top:42px;"><div class="section-header"><h2 class="section-title">🔗 Truyện Bạn Có Thể Thích</h2></div><div class="comics-grid">@foreach($relatedComics as $item)<a href="{{ route('comics.show',$item->slug) }}" class="comic-card-sm"><div class="sm-cover"><img src="{{ $item->cover_image }}" alt="{{ $item->title }}" class="cover-img" loading="lazy"><span class="sm-badge">★ {{ number_format($item->avg_rating,1) }}</span></div><p class="sm-title">{{ $item->title }}</p></a>@endforeach</div></section>
    @endif
  </div>
</main>
@endsection

@push('scripts')
<script>
(() => {
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const COMIC_ID = {{ $comic->id }};
  const LOGGED_IN = {{ auth()->check() ? 'true':'false' }};
  const CURRENT_USER_ID = {{ auth()->id() ?? 'null' }};
  let commentSort = 'newest';
  let commentPage = 1;

  const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[ch]));
  const formatTime = value => { try { return new Intl.DateTimeFormat('vi-VN',{dateStyle:'short',timeStyle:'short'}).format(new Date(value)); } catch { return ''; } };
  const showToast = (message, type='success') => { const el=document.getElementById('action-toast'); if(!el)return; el.textContent=message; el.style.display='block'; el.style.background=type==='error'?'rgba(239,68,68,.18)':'rgba(34,197,94,.16)'; el.style.color=type==='error'?'#f87171':'#4ade80'; el.style.border='1px solid '+(type==='error'?'rgba(239,68,68,.35)':'rgba(34,197,94,.3)'); clearTimeout(el._t); el._t=setTimeout(()=>el.style.display='none',3600); };
  const requestJson = async (url, options={}) => { const res=await fetch(url,{...options,headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':CSRF,...(options.headers||{})}}); const data=await res.json().catch(()=>({})); if(!res.ok) throw Object.assign(new Error(data.message||`HTTP ${res.status}`),{data,status:res.status}); return data; };

  document.querySelectorAll('[data-detail-tab]').forEach(btn => btn.addEventListener('click', () => {
    document.querySelectorAll('[data-detail-tab]').forEach(x=>x.classList.remove('active')); btn.classList.add('active');
    document.querySelectorAll('.detail-tab-pane').forEach(x=>x.classList.remove('active'));
    document.getElementById('detail-tab-'+btn.dataset.detailTab)?.classList.add('active');
    if(btn.dataset.detailTab==='community'){ loadComments(true); loadRatingSummary(); loadReviews(); loadUserRating(); }
  }));

  const sortChapterRows = asc => { const box=document.getElementById('chapter-list'); if(!box)return; const rows=[...box.querySelectorAll('.chapter-row')]; rows.sort((a,b)=>(Number(a.dataset.chapter)-Number(b.dataset.chapter))*(asc?1:-1)); rows.forEach(r=>box.appendChild(r)); };
  document.getElementById('chap-sort-desc')?.addEventListener('click',function(){sortChapterRows(false);this.classList.add('active');document.getElementById('chap-sort-asc')?.classList.remove('active');});
  document.getElementById('chap-sort-asc')?.addEventListener('click',function(){sortChapterRows(true);this.classList.add('active');document.getElementById('chap-sort-desc')?.classList.remove('active');});

  document.getElementById('btn-toggle-library')?.addEventListener('click', async function(){ this.disabled=true; try{ const data=await requestJson(`/api/comics/${COMIC_ID}/toggle-library`,{method:'POST'}); const saved=!!(data.in_library ?? data.is_followed); this.dataset.saved=saved?'1':'0'; document.getElementById('lib-label').textContent=saved?'✓ Đã Theo Dõi':'📚 Theo Dõi Truyện'; this.style.background=saved?'#16a34a':''; this.style.color=saved?'#fff':''; this.style.borderColor=saved?'#16a34a':''; showToast(data.message||'Đã cập nhật Tủ Truyện.'); }catch(e){showToast(e.message,'error')}finally{this.disabled=false;} });
  document.getElementById('btn-toggle-like')?.addEventListener('click', async function(){ this.disabled=true; try{ const data=await requestJson(`/api/comics/${COMIC_ID}/toggle-like`,{method:'POST'}); this.dataset.liked=data.is_liked?'1':'0'; document.getElementById('like-label').textContent=data.is_liked?'❤️ Đã Thích':'🤍 Yêu Thích'; document.getElementById('like-count').textContent=Number(data.like_count||0).toLocaleString(); this.style.background=data.is_liked?'#ef4444':''; this.style.color=data.is_liked?'#fff':''; this.style.borderColor=data.is_liked?'#ef4444':''; showToast(data.message||'Đã cập nhật yêu thích.'); }catch(e){showToast(e.message,'error')}finally{this.disabled=false;} });

  function renderComment(c, reply=false){ const liked=!!c.liked_by_me; const canReply=LOGGED_IN&&!reply; const canDelete=LOGGED_IN&&Number(c.user_id)===Number(CURRENT_USER_ID); const replies=(c.replies||[]).map(r=>renderComment(r,true)).join(''); return `<div class="detail-comment ${reply?'reply':''}" id="comment-${c.id}"><div class="detail-avatar">${escapeHtml((c.user?.name||'U').slice(0,1).toUpperCase())}</div><div class="detail-comment-body"><div class="detail-comment-head"><strong>${escapeHtml(c.user?.name||'Độc giả')}</strong><span style="font-size:11px;color:var(--text-sub)">${formatTime(c.created_at)}</span></div><div class="detail-comment-text">${escapeHtml(c.content)}</div><div style="display:flex;gap:14px;align-items:center"><${LOGGED_IN?'button':'a'} ${LOGGED_IN?'type="button" data-like-comment="'+c.id+'"':'href="{{ route('login') }}"'} class="comment-action ${liked?'liked':''}">${liked?'❤️':'🤍'} <span data-comment-like-count="${c.id}">${Number(c.likes_count||0)}</span></${LOGGED_IN?'button':'a'}>${canReply?`<button type="button" class="comment-action" data-reply-toggle="${c.id}">💬 Trả lời</button>`:''}${canDelete?`<button type="button" class="comment-action" data-delete-comment="${c.id}" style="color:#ef4444">🗑️ Xóa</button>`:''}</div>${canReply?`<div class="reply-editor" id="reply-editor-${c.id}"><textarea rows="2" id="reply-text-${c.id}" placeholder="Trả lời ${escapeHtml(c.user?.name||'độc giả')}..."></textarea><button type="button" class="btn-spotlight-read" data-post-reply="${c.id}" style="padding:7px 12px;height:max-content">Gửi</button></div>`:''}${replies}</div></div>`; }

  async function loadComments(reset=false){ if(reset){commentPage=1;} const list=document.getElementById('detail-comments-list'); if(!list)return; if(reset)list.innerHTML='<p style="color:var(--text-sub)">Đang tải bình luận...</p>'; try{ const data=await requestJson(`/api/comments?comic_id=${COMIC_ID}&sort=${commentSort}&page=${commentPage}`); const page=data.comments; const html=(page.data||[]).map(c=>renderComment(c)).join(''); if(reset) list.innerHTML=html||'<div style="padding:28px;text-align:center;color:var(--text-sub)">Chưa có bình luận nào. Hãy là người đầu tiên mở lời.</div>'; else list.insertAdjacentHTML('beforeend',html); const more=document.getElementById('btn-more-comments'); if(more){more.style.display=page.current_page<page.last_page?'inline-flex':'none';} }catch(e){list.innerHTML='<p style="color:#f87171">Không tải được bình luận.</p>';} }

  document.querySelectorAll('[data-comment-sort]').forEach(btn=>btn.addEventListener('click',function(){document.querySelectorAll('[data-comment-sort]').forEach(x=>x.classList.remove('active'));this.classList.add('active');commentSort=this.dataset.commentSort;loadComments(true);}));
  document.getElementById('btn-more-comments')?.addEventListener('click',()=>{commentPage++;loadComments(false);});
  document.getElementById('btn-post-detail-comment')?.addEventListener('click',()=>postComment(null));
  document.getElementById('detail-comment-input')?.addEventListener('keydown',e=>{if(e.ctrlKey&&e.key==='Enter'){e.preventDefault();postComment(null);}});

  async function postComment(parentId){ const input=parentId?document.getElementById(`reply-text-${parentId}`):document.getElementById('detail-comment-input'); const content=input?.value.trim(); if(!content){showToast('Vui lòng nhập nội dung bình luận.','error');return;} try{ const data=await requestJson('/api/comments',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({comic_id:COMIC_ID,chapter_id:null,parent_id:parentId,content})}); input.value=''; showToast(data.message||'Đã đăng bình luận.',data.is_spam?'error':'success'); if(!data.is_spam)loadComments(true); }catch(e){showToast(e.data?.message||e.message,'error');} }

  document.getElementById('detail-comments-list')?.addEventListener('click',async e=>{
    const like=e.target.closest('[data-like-comment]'); if(like){like.disabled=true;try{const id=like.dataset.likeComment;const data=await requestJson(`/api/comments/${id}/toggle-like`,{method:'POST'});like.classList.toggle('liked',data.is_liked);like.firstChild.textContent=data.is_liked?'❤️ ':'🤍 ';document.querySelector(`[data-comment-like-count="${id}"]`).textContent=data.likes_count;}catch(err){showToast(err.message,'error')}finally{like.disabled=false;}return;}
    const replyToggle=e.target.closest('[data-reply-toggle]'); if(replyToggle){document.getElementById(`reply-editor-${replyToggle.dataset.replyToggle}`)?.classList.toggle('open');return;}
    const reply=e.target.closest('[data-post-reply]'); if(reply){await postComment(reply.dataset.postReply);return;}
    const del=e.target.closest('[data-delete-comment]'); if(del&&confirm('Xóa bình luận này?')){try{await requestJson(`/api/comments/${del.dataset.deleteComment}`,{method:'DELETE'});showToast('Đã xóa bình luận.');loadComments(true);}catch(err){showToast(err.message,'error');}}
  });

  const starBtns=[...document.querySelectorAll('.star-btn')], selected=document.getElementById('selected-score'), review=document.getElementById('rating-review-input'), deleteRating=document.getElementById('btn-delete-rating');
  function renderStars(score){let s='';for(let i=1;i<=5;i++)s+=i<=Math.round(score)?'★':'☆';return s;}
  function setStars(n){if(selected)selected.value=n;starBtns.forEach(b=>b.classList.toggle('active',Number(b.dataset.value)<=n));}
  starBtns.forEach(b=>{b.addEventListener('mouseenter',()=>starBtns.forEach(x=>x.classList.toggle('hovered',Number(x.dataset.value)<=Number(b.dataset.value))));b.addEventListener('mouseleave',()=>starBtns.forEach(x=>x.classList.remove('hovered')));b.addEventListener('click',()=>setStars(Number(b.dataset.value)));});
  async function loadRatingSummary(){try{const j=await requestJson(`/api/comics/${COMIC_ID}/ratings/summary`);const d=j.data;if(!d)return;document.getElementById('rating-avg-display').textContent=Number(d.avg_rating).toFixed(1);document.getElementById('rating-stars-icons').textContent=renderStars(d.avg_rating);document.getElementById('rating-total-display').textContent=d.total_ratings;for(let s=1;s<=5;s++){const info=d.stars?.[s]||{percentage:0};document.getElementById(`bar-fill-${s}`).style.width=`${info.percentage}%`;document.getElementById(`bar-percent-${s}`).textContent=`${info.percentage}%`;}}catch{}}
  async function loadReviews(){const box=document.getElementById('reviews-items');if(!box)return;try{const j=await requestJson(`/api/comics/${COMIC_ID}/ratings/reviews?per_page=5`);const rows=j.data?.data||[];box.innerHTML=rows.length?rows.map(r=>`<div class="review-item"><div class="review-item-header"><strong>${escapeHtml(r.user?.name||'Độc giả')}</strong><span class="review-badge">★ ${Number(r.score).toFixed(1)}</span></div>${r.review?`<p style="margin:8px 0 0;line-height:1.55">${escapeHtml(r.review)}</p>`:''}</div>`).join(''):'<p style="color:var(--text-sub)">Chưa có nhận xét nào.</p>';}catch{box.innerHTML='<p style="color:#f87171">Không tải được nhận xét.</p>';}}
  async function loadUserRating(){if(!LOGGED_IN||!selected)return;try{const j=await requestJson(`/api/comics/${COMIC_ID}/my-rating`);if(j.has_rated){setStars(Math.round(j.score));if(review)review.value=j.review||'';if(deleteRating)deleteRating.style.display='inline-flex';const b=document.getElementById('btn-submit-rating');if(b)b.textContent='Cập Nhật Đánh Giá';}}catch{}}
  document.getElementById('btn-submit-rating')?.addEventListener('click',async function(){const score=Number(selected?.value||0);if(score<1){showToast('Vui lòng chọn số sao.','error');return;}this.disabled=true;try{const j=await requestJson(`/api/comics/${COMIC_ID}/ratings`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({score,review:review?.value.trim()||''})});showToast(j.message||'Đã gửi đánh giá.');if(deleteRating)deleteRating.style.display='inline-flex';this.textContent='Cập Nhật Đánh Giá';loadRatingSummary();loadReviews();}catch(e){showToast(e.data?.message||e.message,'error')}finally{this.disabled=false;}});
  deleteRating?.addEventListener('click',async function(){if(!confirm('Xóa đánh giá này?'))return;this.disabled=true;try{const j=await requestJson(`/api/comics/${COMIC_ID}/ratings`,{method:'DELETE'});setStars(0);if(review)review.value='';this.style.display='none';document.getElementById('btn-submit-rating').textContent='Gửi Đánh Giá';showToast(j.message||'Đã xóa đánh giá.');loadRatingSummary();loadReviews();}catch(e){showToast(e.message,'error')}finally{this.disabled=false;}});

  loadComments(true); loadRatingSummary(); loadReviews(); loadUserRating();
})();
</script>
@endpush
