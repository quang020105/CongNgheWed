<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Quiz Viewer — Tải & Lưu vào DB</title>
  <style>
    :root{
      --bg:#f6fbff; --card:#fff; --accent:#0b74de; --muted:#666;
      --correct: #e6ffef; --incorrect:#fff1f0;
    }
    body{
      margin:0; font-family: Inter, Roboto, "Segoe UI", Arial, sans-serif;
      background: linear-gradient(180deg, #eef7ff 0%, var(--bg) 100%); color:#111;
      -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;
    }
    header{padding:18px 20px; display:flex; gap:12px; align-items:center; border-bottom:1px solid #e6eef7; background:rgba(255,255,255,0.6);}
    h1{margin:0; font-size:18px; color:var(--accent)}
    .container{max-width:920px; margin:28px auto; padding:0 16px;}
    .card{background:var(--card); border-radius:12px; padding:18px; box-shadow:0 6px 18px rgba(12,40,80,0.06); margin-bottom:16px;}
    .file-row{display:flex; gap:10px; align-items:center; flex-wrap:wrap}
    input[type=file]{padding:6px}
    .meta{color:var(--muted); font-size:13px}
    .question{padding:14px; border-radius:10px; margin-bottom:12px; border:1px solid #eef4fb}
    .q-title{font-weight:600; margin-bottom:8px}
    .options{display:flex; flex-direction:column; gap:8px}
    label.option{display:flex; gap:10px; align-items:center; padding:8px 10px; border-radius:8px; border:1px solid transparent; cursor:pointer;}
    input[type=radio]{accent-color:var(--accent)}
    .controls{display:flex; gap:8px; margin-top:12px; align-items:center}
    button{background:var(--accent); color:white; border:0; padding:10px 14px; border-radius:8px; cursor:pointer}
    button.secondary{background:#fff; color:var(--accent); border:1px solid #cfe6ff}
    .result{padding:12px; border-radius:10px; margin-top:10px}
    .answer-correct{background:var(--correct); border-left:4px solid #18a36b}
    .answer-wrong{background:var(--incorrect); border-left:4px solid #e03b3b}
    .small-muted{font-size:13px; color:var(--muted)}
    .hidden{display:none}
  </style>
</head>
<body>
  <header>
    <h1>Quiz Viewer — Lấy từ DB</h1>
    <div style="margin-left:auto" class="small-muted">Kết hợp upload -> parse -> lưu DB</div>
  </header>

  <main class="container">
    <div class="card" id="uploaderCard">
      <strong>Upload file Quiz.txt (Admin)</strong>
      <div class="small-muted">Upload file định dạng giống mẫu -> server sẽ parse và chèn vào CSDL.</div>
      <div style="margin-top:8px" class="file-row">
        <input id="quizfile" type="file" accept=".txt,text/plain">
        <label style="display:flex;align-items:center;gap:8px">
          <input id="replaceCheckbox" type="checkbox" value="1"> <span style="font-size:13px;margin-left:6px">Replace (xóa tất cả câu trước khi chèn)</span>
        </label>
        <button id="uploadBtn" class="secondary">Upload & Lưu vào DB</button>
        <div id="uploadMsg" class="small-muted" style="margin-left:auto"></div>
      </div>
    </div>

    <div id="quizArea" class="card">
      <div id="quizList"></div>
      <div class="controls">
        <button id="submitBtn">Nộp bài</button>
        <button id="reloadBtn" class="secondary">Tải lại câu hỏi</button>
        <div style="margin-left:auto" id="scoreBox"></div>
      </div>
      <div id="feedback" class="result hidden"></div>
    </div>
  </main>

  <script>
    const quizList = document.getElementById('quizList');
    const uploadBtn = document.getElementById('uploadBtn');
    const quizfile = document.getElementById('quizfile');
    const replaceCheckbox = document.getElementById('replaceCheckbox');
    const uploadMsg = document.getElementById('uploadMsg');

    let questions = [];

    function escapeHtml(s){
      return (s||'').toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function renderQuestions(qs){
      quizList.innerHTML = '';
      qs.forEach((q, idx) => {
        const div = document.createElement('div');
        div.className = 'question';
        const title = document.createElement('div'); title.className='q-title';
        title.textContent = (idx+1) + '. ' + q.question;
        div.appendChild(title);
        const opts = document.createElement('div'); opts.className='options';
        ['A','B','C','D'].forEach(k=>{
          if (q.options[k] !== null && q.options[k] !== undefined && q.options[k] !== "") {
            const id = `q${idx}_${k}`;
            const lbl = document.createElement('label'); lbl.className='option'; lbl.htmlFor=id;
            const r = document.createElement('input'); r.type='radio'; r.name=`q${idx}`; r.id=id; r.value=k;
            const span = document.createElement('span'); span.innerHTML = `<strong>${k}.</strong> ${escapeHtml(q.options[k])}`;
            lbl.appendChild(r); lbl.appendChild(span); opts.appendChild(lbl);
          }
        });
        if (Object.values(q.options).every(v=>!v)) {
          const n = document.createElement('div'); n.className='small-muted'; n.textContent='Không có phương án.';
          opts.appendChild(n);
        }
        div.appendChild(opts);
        quizList.appendChild(div);
      });
    }

    async function loadQuestions() {
      try {
        const res = await fetch('api.php');
        const j = await res.json();
        if (j.ok) {
          questions = j.questions;
          renderQuestions(questions);
          document.getElementById('scoreBox').innerHTML = `<span class="small-muted">${questions.length} câu</span>`;
        } else {
          quizList.innerHTML = '<div class="small-muted">Không lấy được dữ liệu từ server.</div>';
        }
      } catch (err) {
        quizList.innerHTML = '<div class="small-muted">Lỗi kết nối tới server.</div>';
      }
    }

    uploadBtn.addEventListener('click', async ()=>{
      if (!quizfile.files[0]) {
        alert('Chọn file Quiz.txt trước khi upload.');
        return;
      }
      const fd = new FormData();
      fd.append('quizfile', quizfile.files[0]);
      if (replaceCheckbox.checked) fd.append('replace', '1');
      uploadMsg.textContent = 'Đang upload...';
      try {
        const res = await fetch('upload.php', {method:'POST', body: fd});
        const j = await res.json();
        if (j.ok) {
          uploadMsg.textContent = `Đã chèn: ${j.inserted} câu (đã phân tích ${j.parsed}).`;
          // reload questions
          await loadQuestions();
        } else if (j.error) {
          uploadMsg.textContent = 'Lỗi: ' + j.error;
        } else {
          uploadMsg.textContent = 'Upload hoàn tất.';
        }
      } catch (err) {
        uploadMsg.textContent = 'Lỗi upload';
        console.error(err);
      }
      setTimeout(()=>uploadMsg.textContent='', 5000);
    });

    document.getElementById('submitBtn').addEventListener('click', ()=>{
      if (!questions.length) return alert('Không có câu hỏi.');
      const qDivs = document.querySelectorAll('.question');
      let correct = 0, totalWithKey = 0;
      qDivs.forEach((d, i)=>{
        const q = questions[i];
        const sel = d.querySelector('input[type=radio]:checked');
        const selVal = sel ? sel.value : null;
        d.classList.remove('answer-correct','answer-wrong');
        if (q.answer) {
          totalWithKey++;
          if (selVal === q.answer) { correct++; d.classList.add('answer-correct'); }
          else d.classList.add('answer-wrong');
          // show info
          let info = d.querySelector('.keyinfo');
          if (!info) { info = document.createElement('div'); info.className='small-muted keyinfo'; info.style.marginTop='8px'; d.appendChild(info); }
          const corr = q.options[q.answer] ? `${q.answer}. ${q.options[q.answer]}` : q.answer;
          const user = selVal ? (q.options[selVal] ? `${selVal}. ${q.options[selVal]}` : selVal) : '(chưa chọn)';
          info.innerHTML = `<strong>Đáp án đúng:</strong> ${escapeHtml(corr)}<br><strong>Đã chọn:</strong> ${escapeHtml(user)}`;
        } else {
          // no key
          let info = d.querySelector('.keyinfo');
          if (!info) { info = document.createElement('div'); info.className='small-muted keyinfo'; info.style.marginTop='8px'; d.appendChild(info); }
          info.innerHTML = '<strong>Không có đáp án trong DB cho câu này.</strong>';
        }
      });
      document.getElementById('scoreBox').innerHTML = `<strong>Điểm: ${correct}/${totalWithKey>0?totalWithKey:questions.length}</strong>`;
      const fb = document.getElementById('feedback');
      fb.classList.remove('hidden');
      const perc = totalWithKey>0 ? (correct/totalWithKey*100).toFixed(1) : (correct/questions.length*100).toFixed(1);
      fb.innerHTML = `Bạn đúng <strong>${correct}</strong> trên <strong>${totalWithKey>0?totalWithKey:questions.length}</strong>. (${perc}%)`;
    });

    document.getElementById('reloadBtn').addEventListener('click', loadQuestions);

    // load at start
    loadQuestions();
  </script>
</body>
</html>
