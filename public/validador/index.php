<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OPECS WEB - Validador</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans text-gray-700 h-screen flex flex-col">

    <div class="max-w-6xl mx-auto py-8 px-4 w-full flex-grow">
        <header class="flex justify-between items-center mb-8 bg-white p-6 rounded-xl shadow-sm">
            <div>
                <h1 class="text-2xl font-black text-gray-800 tracking-tight">OPECS <span class="text-blue-600">WEB</span></h1>
                <p class="text-[10px] text-gray-400 font-bold uppercase">Validador Oficial de Mídia</p>
            </div>
            </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-fit">
                <h2 class="text-xs font-bold text-gray-400 uppercase mb-4">Configuração do Upload</h2>
                
                <label class="block text-[10px] font-black text-gray-500 mb-1">SELECIONE O PAINEL</label>
                <select id="configSelect" onchange="updateInfo()" class="w-full p-2 border rounded-lg mb-4 text-xs font-bold bg-gray-50 outline-none focus:border-blue-500 transition"></select>
                
                <div id="specBox" class="p-3 bg-blue-50 text-blue-700 rounded-lg border border-blue-100 text-[10px] font-mono mb-6 text-center">
                    Carregando formatos...
                </div>
                
                <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 hover:border-blue-300 transition group">
                    <div class="text-center group-hover:-translate-y-1 transition-transform">
                        <i class="fas fa-cloud-upload-alt text-gray-300 group-hover:text-blue-400 text-3xl mb-2"></i>
                        <p id="fileName" class="text-[10px] text-gray-400 font-bold px-4 truncate w-40">Clique para escolher o arquivo</p>
                    </div>
                    <input type="file" id="mediaInput" class="hidden" accept="video/mp4,image/jpeg,image/png">
                </label>

                <div id="resultArea" class="hidden mt-6 animate-fade-in">
                    <div id="statusBadge" class="text-center py-2 rounded-lg font-black text-xs mb-4"></div>
                    <div id="detailList" class="text-[10px] space-y-2 mb-4 p-3 bg-gray-50 rounded-lg border border-gray-100"></div>
                    
                    <button id="saveBtn" onclick="uploadMedia()" class="w-full bg-green-600 text-white py-3 rounded-xl font-bold text-xs shadow-lg hover:bg-green-700 hover:shadow-xl transition-all flex justify-center items-center gap-2">
                        <span>ENVIAR PARA APROVAÇÃO</span> <i class="fas fa-paper-plane"></i>
                    </button>
                    <p id="uploadingMsg" class="hidden text-center text-[10px] text-gray-400 mt-2 font-bold animate-pulse">Enviando arquivo...</p>
                </div>
            </div>

            <div class="md:col-span-2 bg-gray-900 rounded-xl flex items-center justify-center min-h-[500px] border-4 border-white shadow-xl overflow-hidden relative">
                <div id="preview" class="text-gray-600 text-center relative z-10">
                    <i class="fas fa-play-circle text-6xl opacity-20 mb-4 block"></i>
                    <p class="text-xs opacity-40 font-bold">O preview aparecerá aqui</p>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center py-6 text-[10px] text-gray-400 font-bold">
        &copy; <?= date('Y') ?> OPECS Mídia Exterior. Sistema Integrado.
    </footer>

    <script>
        let configs = [];
        let currentFile = null;

        async function init() {
            try {
                const fd = new FormData(); fd.append('action', 'list_configs');
                const res = await fetch('api.php', { method: 'POST', body: fd });
                configs = await res.json();
                
                const sel = document.getElementById('configSelect');
                sel.innerHTML = '<option value="" disabled selected>-- Escolha um Painel --</option>';
                
                if(configs.length === 0) {
                     sel.innerHTML = '<option disabled>Nenhum painel digital cadastrado no Admin</option>';
                     document.getElementById('specBox').innerHTML = 'Sem conexão com o Inventário.';
                     return;
                }

                configs.forEach(c => {
                    let opt = document.createElement('option');
                    opt.value = c.id; 
                    opt.text = c.name; 
                    sel.add(opt);
                });
                
                // Seleciona o primeiro por conveniência se quiser, ou deixa em branco
                // sel.selectedIndex = 1; 
                // updateInfo(); 

            } catch (error) {
                console.error(error);
                document.getElementById('specBox').innerHTML = 'Erro ao carregar formatos.';
            }
        }

        function updateInfo() {
            const val = document.getElementById('configSelect').value;
            if(!val) return;
            const c = configs.find(x => x.id == val);
            if(c) document.getElementById('specBox').innerHTML = `FORMATO EXIGIDO:<br><span class="text-lg">${c.w}x${c.h}px</span><br>DURAÇÃO: ${c.d}s`;
        }

        document.getElementById('mediaInput').onchange = function(e) {
            currentFile = e.target.files[0];
            if(!currentFile) return;
            
            const selValue = document.getElementById('configSelect').value;
            if(!selValue) {
                alert('Por favor, selecione um painel primeiro.');
                this.value = ''; // Limpa o input
                return;
            }

            document.getElementById('fileName').innerText = currentFile.name;
            const url = URL.createObjectURL(currentFile);
            const preview = document.getElementById('preview'); 
            preview.innerHTML = ''; // Limpa ícone
            
            const cfg = configs.find(x => x.id == selValue);

            if(currentFile.type.startsWith('video')) {
                const v = document.createElement('video'); 
                v.src = url; 
                v.controls = true;
                v.autoplay = true;
                v.muted = true;
                v.className = "max-h-[480px] max-w-full rounded shadow-2xl";
                v.onloadedmetadata = () => validate(v.videoWidth, v.videoHeight, v.duration, cfg, true);
                preview.appendChild(v);
            } else {
                const i = new Image(); 
                i.src = url; 
                i.className = "max-h-[480px] max-w-full rounded shadow-2xl";
                i.onload = () => validate(i.width, i.height, 0, cfg, false);
                preview.appendChild(i);
            }
        };

        function validate(w, h, d, cfg, isVideo) {
            // Tolerância de 1px para arredondamentos de vídeo
            const okW = Math.abs(w - cfg.w) <= 1 && Math.abs(h - cfg.h) <= 1;
            // Tolerância de 0.9s na duração
            const okD = isVideo ? (d >= cfg.d - 0.5 && d <= cfg.d + 0.9) : true;
            
            document.getElementById('resultArea').classList.remove('hidden');
            const badge = document.getElementById('statusBadge');
            
            let htmlDetails = `<div>DIMENSÕES: ${w}x${h}px <span class="${okW?'text-green-600':'text-red-500 font-bold'}">(${okW?'OK':'INCORRETO'})</span></div>`;
            if(isVideo) {
                htmlDetails += `<div>DURAÇÃO: ${d.toFixed(1)}s <span class="${okD?'text-green-600':'text-red-500 font-bold'}">(${okD?'OK':'INCORRETO'})</span></div>`;
            }

            document.getElementById('detailList').innerHTML = htmlDetails;

            if(okW && okD) {
                badge.innerText = "ARQUIVO DENTRO DO PADRÃO ✅"; 
                badge.className = "py-3 rounded-lg font-black text-xs mb-4 bg-green-100 text-green-700 border border-green-200";
                document.getElementById('saveBtn').classList.remove('hidden');
            } else {
                badge.innerText = "ARQUIVO FORA DO PADRÃO ❌"; 
                badge.className = "py-3 rounded-lg font-black text-xs mb-4 bg-red-100 text-red-700 border border-red-200";
                document.getElementById('saveBtn').classList.add('hidden');
            }
        }

        window.uploadMedia = async function() {
            const btn = document.getElementById('saveBtn');
            const msg = document.getElementById('uploadingMsg');
            
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            btn.disabled = true;
            msg.classList.remove('hidden');

            try {
                const fd = new FormData();
                fd.append('action', 'upload');
                fd.append('mediaFile', currentFile);
                fd.append('config_name', document.getElementById('configSelect').selectedOptions[0].text);
                
                const res = await fetch('api.php', { method: 'POST', body: fd }).then(r => r.json());
                
                if(res.success) {
                    window.location.href = res.url; // Redireciona para a tela de sucesso
                } else {
                    alert('Erro no envio: ' + (res.error || 'Desconhecido'));
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    btn.disabled = false;
                    msg.classList.add('hidden');
                }
            } catch (e) {
                alert('Erro de conexão.');
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
                btn.disabled = false;
                msg.classList.add('hidden');
            }
        };

        init();
    </script>
</body>
</html>