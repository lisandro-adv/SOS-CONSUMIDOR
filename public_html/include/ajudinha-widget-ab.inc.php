<?php
/* Ajudinha experimental: animacao em video com remocao local do fundo claro. */
if (defined('SOS_AJUDINHA_RENDERED')) {
    return;
}
define('SOS_AJUDINHA_RENDERED', true);

$ajudinha_root = defined('PROJECT_ROOT') ? PROJECT_ROOT : '/';
$ajudinha_root = rtrim((string) $ajudinha_root, '/') . '/';
$ajudinha_asset = $ajudinha_root . 'assets/img/ajudinha/ajudinha-v5-alpha-b817b0c7.png';
$ajudinha_video = $ajudinha_root . 'assets/video/ajudinha/ajudinha-video-a4b17375.mp4';
$ajudinha_responde_url = $ajudinha_root . 'ia-consumidor/';
?>
<style>
    .sos-ajudinha{position:fixed;top:var(--sos-ajudinha-top,240px);right:max(12px,calc((100vw - 1180px)/2 + 24px));z-index:1200;font-family:Arial,sans-serif}
    .sos-ajudinha-toggle{display:flex;flex-direction:column;align-items:center;gap:5px;padding:0;border:0;background:transparent;color:#fff;cursor:pointer}
    .sos-ajudinha-toggle:focus-visible{outline:3px solid #f2c75c;outline-offset:5px;border-radius:24px}
    .sos-ajudinha-character{position:relative;display:block;width:220px;height:220px}
    .sos-ajudinha-stage{position:relative;display:block;width:220px;height:220px;overflow:visible;border:0;border-radius:0;background:transparent;box-shadow:none;filter:drop-shadow(0 10px 12px #18324738);transition:filter .18s ease}
    .sos-ajudinha-toggle:hover .sos-ajudinha-stage,.sos-ajudinha-toggle:focus-visible .sos-ajudinha-stage{filter:drop-shadow(0 10px 12px #18324738) brightness(1.035)}
    .sos-ajudinha-video{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none}
    .sos-ajudinha-poster{position:absolute;inset:0;z-index:1;display:block;width:100%;height:100%;object-fit:contain;pointer-events:none;user-select:none;transition:opacity .18s ease}
    .sos-ajudinha-video-canvas{position:absolute;inset:0;z-index:8;display:block;width:100%;height:100%;opacity:0;pointer-events:none;user-select:none;transition:opacity .18s ease}
    .sos-ajudinha.is-video-ready .sos-ajudinha-poster{opacity:0}
    .sos-ajudinha.is-video-ready .sos-ajudinha-video-canvas{opacity:1}
    .sos-ajudinha-toggle>.sos-ajudinha-label{display:flex;flex-direction:column;align-items:center;gap:2px;padding:8px 20px 9px;border:2px solid #fff;border-radius:999px;background:#07557f;box-shadow:0 6px 18px #18324735;font-size:15px;font-weight:800;line-height:1.12;white-space:nowrap}
    .sos-ajudinha-toggle>.sos-ajudinha-label small{font-size:13px;font-weight:700}
    .sos-ajudinha-panel{position:absolute;right:calc(100% + 28px);top:18%;box-sizing:border-box;width:min(510px,calc(100vw - 28px));padding:32px 30px 28px;border:3px solid #83bff2;border-radius:40px 46px 38px 44px;background:linear-gradient(145deg,#fff 0%,#f8fbff 100%);color:#183247;box-shadow:0 14px 34px #18324724,0 0 10px #83bff255;transform:translateY(-50%)}
    .sos-ajudinha-panel::before{content:"";position:absolute;right:-31px;top:52%;width:37px;height:32px;background:#07557f;clip-path:polygon(0 0,100% 52%,0 100%);transform:translateY(-50%)}
    .sos-ajudinha-panel::after{content:"";position:absolute;right:-24px;top:52%;width:30px;height:24px;background:#fff;clip-path:polygon(0 0,100% 52%,0 100%);transform:translateY(-50%)}
    .sos-ajudinha-panel[hidden]{display:none}
    .sos-ajudinha-panel-head{display:flex;align-items:center;margin-bottom:8px}
    .sos-ajudinha-panel h2{box-sizing:border-box;width:100%;max-width:100%;margin:0;padding:0 42px 0 4px;color:#09295b;font-size:26px;line-height:1.16;letter-spacing:-.25px;overflow-wrap:anywhere;word-break:normal}
    .sos-ajudinha-panel h2 span{display:block}
    .sos-ajudinha-panel h2 span+span{margin-top:6px}
    .sos-ajudinha-panel h2 span:nth-child(2){font-size:.9em;white-space:nowrap;letter-spacing:-.5px}
    .sos-ajudinha-panel h2 span:first-child{font-weight:800}
    .sos-ajudinha-panel h2 em{font-style:normal;font-weight:800;color:#f15a24}
    .sos-ajudinha-panel p{box-sizing:border-box;width:100%;max-width:100%;margin:14px 0 17px;color:#405466;font-size:19px;line-height:1.35;overflow-wrap:anywhere}
    .sos-ajudinha-actions{display:grid;grid-template-columns:1fr;gap:10px}
    .sos-ajudinha-actions a{display:flex;align-items:center;justify-content:flex-start;gap:16px;min-height:68px;box-sizing:border-box;padding:10px 20px;border:2px solid #e0ebf6;border-radius:19px;color:#09295b;background:linear-gradient(145deg,#fff 0%,#f7fbff 100%);box-shadow:0 5px 11px #17466e18;font-size:17px;font-weight:800;line-height:1.18;text-align:left;text-decoration:none;transition:transform .15s ease,box-shadow .15s ease,border-color .15s ease}
    .sos-ajudinha-action-copy{display:flex;flex-direction:column;gap:3px;min-width:0}
    .sos-ajudinha-action-copy strong{display:block;font-size:17px;line-height:1.12}
    .sos-ajudinha-action-copy small{display:block;color:#536879;font-size:12px;font-weight:600;line-height:1.25}
    .sos-ajudinha-action-icon{display:inline-flex;align-items:center;justify-content:center;flex:0 0 48px;width:48px;height:48px;border-radius:14px;background:transparent;color:#2376ce;line-height:1}
    .sos-ajudinha-action-icon svg{display:block;width:30px;height:30px;fill:none;stroke:currentColor;stroke-linecap:round;stroke-linejoin:round;stroke-width:1.9}
    .sos-ajudinha-actions a:nth-child(1) .sos-ajudinha-action-icon svg{transform:scale(1.34)}
    .sos-ajudinha-actions a:nth-child(2) .sos-ajudinha-action-icon svg{transform:scale(.92)}
    .sos-ajudinha-actions a:nth-child(3) .sos-ajudinha-action-icon svg{transform:scale(1.16)}
    .sos-ajudinha-actions a:nth-child(2) .sos-ajudinha-action-icon{border-radius:12px 18px 18px 5px;background:linear-gradient(145deg,#ff6b20,#f04413);color:#fff;box-shadow:0 4px 8px #f0441328}
    .sos-ajudinha-actions a:nth-child(1) .sos-ajudinha-action-icon{border-radius:50%;background:linear-gradient(145deg,#ff4a86,#7525d5);color:#fff;box-shadow:0 4px 8px #7525d528}
    .sos-ajudinha-actions a:hover,.sos-ajudinha-actions a:focus-visible{border-color:#83bff2;background:#f5fbff;box-shadow:0 8px 18px #17466e25;outline:none;transform:translateY(-1px)}
    .sos-ajudinha-close{position:absolute;top:18px;right:21px;border:0;background:transparent;color:#183247;font-size:32px;line-height:1;cursor:pointer}
    .sos-ajudinha-close:focus-visible{outline:2px solid #07557f;outline-offset:2px}
    .sos-ajudinha-test-placement{position:sticky;top:18px;z-index:50;display:flex;justify-content:flex-start;margin:-18px 0 30px -8px;padding-top:8px}
    .sos-ajudinha-test-placement .sos-ajudinha{position:relative;top:auto;right:auto;z-index:auto;width:max-content}
    @media(max-width:767px){.sos-ajudinha-test-placement{position:relative;top:auto;justify-content:center;margin:0 0 28px;padding-top:0}}
    @media(max-width:580px){.sos-ajudinha{right:8px}.sos-ajudinha-character,.sos-ajudinha-stage{width:168px;height:168px}.sos-ajudinha-toggle>.sos-ajudinha-label{padding:7px 14px 8px;font-size:13px}.sos-ajudinha-toggle>.sos-ajudinha-label small{font-size:11px}.sos-ajudinha-panel{right:50%;top:auto;bottom:calc(100% + 26px);width:min(380px,calc(100vw - 20px));padding:31px 26px 26px;border-radius:36px 42px 34px 40px;transform:translateX(50%)}.sos-ajudinha-panel::before{right:26%;top:auto;bottom:-30px;clip-path:polygon(0 0,100% 0,70% 100%);transform:none}.sos-ajudinha-panel::after{right:calc(26% + 4px);top:auto;bottom:-22px;clip-path:polygon(0 0,100% 0,70% 100%);transform:none}.sos-ajudinha-panel h2{max-width:100%;padding-right:40px;font-size:21px}.sos-ajudinha-panel h2 span:nth-child(2){white-space:normal;font-size:1em;letter-spacing:normal}.sos-ajudinha-panel p{max-width:100%;font-size:17px}.sos-ajudinha-actions{grid-template-columns:1fr;gap:10px}.sos-ajudinha-actions a{min-height:64px;font-size:16px}.sos-ajudinha-action-icon{flex-basis:46px;width:46px;height:46px;border-radius:14px}.sos-ajudinha-close{top:15px;right:23px}}
</style>
<aside class="sos-ajudinha is-video" id="sos-ajudinha" aria-label="Ajudinha, atalhos de orientacao">
    <section class="sos-ajudinha-panel" id="sos-ajudinha-panel" role="dialog" aria-labelledby="sos-ajudinha-title" aria-describedby="sos-ajudinha-copy" hidden>
        <button class="sos-ajudinha-close" id="sos-ajudinha-close" type="button" aria-label="Fechar Ajudinha">×</button>
        <div class="sos-ajudinha-panel-head">
            <h2 id="sos-ajudinha-title"><span>Oi, eu sou o <em>Ajudinha!</em></span><span>O assistente virtual do SOS Consumidor.</span></h2>
        </div>
        <p id="sos-ajudinha-copy">Em que eu posso te ajudar:</p>
        <nav class="sos-ajudinha-actions" aria-label="Atalhos da Ajudinha">
            <a href="<?php echo htmlspecialchars($ajudinha_responde_url, ENT_QUOTES, 'UTF-8'); ?>"><span class="sos-ajudinha-action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M9.2 9a3.1 3.1 0 1 1 5.2 2.3c-1.3 1.1-2.4 1.5-2.4 3.2"/><path d="M12 18h.01"/></svg></span><span class="sos-ajudinha-action-copy"><strong>Tirar uma dúvida</strong><small>Encontre respostas sobre seus direitos.</small></span></a>
            <a href="<?php echo htmlspecialchars($ajudinha_root . 'juros/', ENT_QUOTES, 'UTF-8'); ?>"><span class="sos-ajudinha-action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="8" cy="7" r="2.5"/><circle cx="16" cy="17" r="2.5"/><path d="M18.5 5.5 5.5 18.5"/></svg></span><span class="sos-ajudinha-action-copy"><strong>Comparar juros cobrados</strong><small>Veja se a taxa está acima da média.</small></span></a>
            <a href="<?php echo htmlspecialchars($ajudinha_root . 'calculos/', ENT_QUOTES, 'UTF-8'); ?>"><span class="sos-ajudinha-action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="5" y="2.5" width="14" height="19" rx="2"/><rect x="8" y="5.5" width="8" height="3" rx=".5"/><path d="M8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01M16 16h.01M8 20h8"/></svg></span><span class="sos-ajudinha-action-copy"><strong>Calcular valores</strong><small>Atualize dívidas, parcelas e correção.</small></span></a>
        </nav>
    </section>
    <button class="sos-ajudinha-toggle" id="sos-ajudinha-toggle" type="button" aria-expanded="false" aria-controls="sos-ajudinha-panel">
        <span class="sos-ajudinha-character" aria-hidden="true">
            <span class="sos-ajudinha-stage">
                <img class="sos-ajudinha-poster" src="<?php echo htmlspecialchars($ajudinha_asset, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                <video class="sos-ajudinha-video" id="sos-ajudinha-video" muted loop playsinline preload="metadata" poster="<?php echo htmlspecialchars($ajudinha_asset, ENT_QUOTES, 'UTF-8'); ?>">
                    <source src="<?php echo htmlspecialchars($ajudinha_video, ENT_QUOTES, 'UTF-8'); ?>" type="video/mp4">
                </video>
                <canvas class="sos-ajudinha-video-canvas" id="sos-ajudinha-video-canvas" width="320" height="320" aria-hidden="true"></canvas>
            </span>
        </span>
        <span class="sos-ajudinha-label"><span>Quer uma Ajudinha?</span><small>Clique aqui!</small></span>
    </button>
</aside>
<script>
(function(){
    var root=document.getElementById('sos-ajudinha'),toggle=document.getElementById('sos-ajudinha-toggle'),panel=document.getElementById('sos-ajudinha-panel'),close=document.getElementById('sos-ajudinha-close'),video=document.getElementById('sos-ajudinha-video'),canvas=document.getElementById('sos-ajudinha-video-canvas');
    if(!root||!toggle||!panel||!close)return;
    var media=window.matchMedia?window.matchMedia('(prefers-reduced-motion: reduce)'):null;
    var canvasFrame=0,videoCallback=0;
    var canvasContext=canvas&&canvas.getContext?canvas.getContext('2d',{willReadFrequently:true}):null;
    function reduced(){return media&&media.matches;}
    function backgroundPixel(data,index){
        if(data[index+3]===0)return true;
        var red=data[index],green=data[index+1],blue=data[index+2];
        var darkest=Math.min(red,green,blue),lightest=Math.max(red,green,blue);
        return darkest>214&&lightest-darkest<34;
    }
    function removeVideoBackground(){
        if(!canvasContext||!video.videoWidth||!video.videoHeight)return false;
        var width=canvas.width,height=canvas.height,scale=Math.min(width/video.videoWidth,height/video.videoHeight);
        var drawWidth=Math.round(video.videoWidth*scale),drawHeight=Math.round(video.videoHeight*scale);
        var left=Math.floor((width-drawWidth)/2),top=Math.floor((height-drawHeight)/2);
        canvasContext.clearRect(0,0,width,height);
        canvasContext.drawImage(video,left,top,drawWidth,drawHeight);
        var frame=canvasContext.getImageData(0,0,width,height),data=frame.data,total=width*height;
        var visited=new Uint8Array(total),queue=new Int32Array(total),head=0,tail=0;
        function add(pixel){var offset=pixel*4;if(pixel<0||pixel>=total||visited[pixel]||!backgroundPixel(data,offset))return;visited[pixel]=1;queue[tail++]=pixel;}
        var x,y,pixel;
        for(x=0;x<width;x++){add(x);add((height-1)*width+x);}
        for(y=1;y<height-1;y++){add(y*width);add(y*width+width-1);}
        while(head<tail){
            pixel=queue[head++];x=pixel%width;y=(pixel/width)|0;
            if(x>0)add(pixel-1);if(x<width-1)add(pixel+1);if(y>0)add(pixel-width);if(y<height-1)add(pixel+width);
        }
        for(pixel=0;pixel<total;pixel++){if(visited[pixel])data[pixel*4+3]=0;}
        canvasContext.putImageData(frame,0,0);
        return true;
    }
    function renderVideo(){
        canvasFrame=0;videoCallback=0;
        if(!video||video.paused||video.ended)return;
        if(removeVideoBackground())root.classList.add('is-video-ready');
        if(video.requestVideoFrameCallback){videoCallback=video.requestVideoFrameCallback(renderVideo);}else{canvasFrame=window.requestAnimationFrame(renderVideo);}
    }
    function startVideoRendering(){
        if(canvasFrame)window.cancelAnimationFrame(canvasFrame);
        if(videoCallback&&video.cancelVideoFrameCallback)video.cancelVideoFrameCallback(videoCallback);
        canvasFrame=0;videoCallback=0;renderVideo();
    }
    function syncVideo(forceStart){
        if(!video)return;
        if(!reduced()||forceStart){
            video.muted=true;video.defaultMuted=true;
            var attempt=video.play();
            if(attempt&&attempt.then)attempt.then(startVideoRendering).catch(function(){});else startVideoRendering();
        }else{video.pause();}
    }
    function updateMotion(){root.classList.toggle('is-paused',reduced());syncVideo();}
    updateMotion();
    if(media){if(media.addEventListener)media.addEventListener('change',updateMotion);else if(media.addListener)media.addListener(updateMotion);}
    if(video){video.addEventListener('loadeddata',function(){removeVideoBackground();syncVideo();});video.addEventListener('seeked',removeVideoBackground);}
    toggle.addEventListener('click',function(){var open=toggle.getAttribute('aria-expanded')==='true';toggle.setAttribute('aria-expanded',String(!open));panel.hidden=open;if(!open)close.focus();});
    close.addEventListener('click',function(){panel.hidden=true;toggle.setAttribute('aria-expanded','false');toggle.focus();});
    document.addEventListener('keydown',function(event){if(event.key==='Escape'&&!panel.hidden){panel.hidden=true;toggle.setAttribute('aria-expanded','false');toggle.focus();}});
})();
</script>
