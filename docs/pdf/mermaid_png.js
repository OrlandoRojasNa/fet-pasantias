// Convierte un bloque mermaid (archivo .mmd) en PNG: node mermaid_png.js entrada.mmd salida.png
const fs=require('fs');const {chromium}=require('playwright');
(async()=>{
 const [inp,out]=process.argv.slice(2);
 const js=fs.readFileSync(require.resolve('mermaid/dist/mermaid.min.js'),'utf8');
 const b=await chromium.launch();const p=await b.newPage({deviceScaleFactor:3});
 await p.setContent(`<html><body style="margin:0;background:#fff"><pre class="mermaid">${fs.readFileSync(inp,'utf8').replace(/</g,'&lt;')}</pre><script>${js}</script><script>window.d=false;mermaid.initialize({startOnLoad:false,theme:'neutral',fontFamily:'Times New Roman'});mermaid.run({querySelector:'pre.mermaid'}).finally(()=>window.d=true)</script></body></html>`);
 await p.waitForFunction('window.d');
 await (await p.$('pre.mermaid svg')).screenshot({path:out});await b.close();
})();
