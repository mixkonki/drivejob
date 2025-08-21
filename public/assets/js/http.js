class HttpClient {
  constructor(base="") { this.base=base; this.csrf=null; }
  async getCsrf(){
    if (this.csrf) return this.csrf;
    const res = await fetch(this.base + "../api/csrf_token.php",{credentials:"include"});
    const j = await res.json(); this.csrf = j.csrf_token || null; return this.csrf;
  }
  async request(path,{method="GET",headers={},body=null,retries=3,backoffMs=400}={}){
    const url = this.base + path;
    const init = {method, headers:{"X-Requested-With":"fetch",...headers}, credentials:"include"};
    if (body && typeof body==="object" && !(body instanceof FormData)) { init.headers["Content-Type"]="application/json"; init.body=JSON.stringify(body); }
    else if (body) { init.body=body; }
    if (["POST","PUT","PATCH","DELETE"].includes(method)){ const t=await this.getCsrf(); if(t) init.headers["X-CSRF-Token"]=t; }
    let last; for(let i=0;i<=retries;i++){ const res=await fetch(url,init); if(res.status!==429){ const txt=await res.text(); try{return JSON.parse(txt);}catch{return {raw:txt,status:res.status};} }
      last=res; const ra=res.headers.get("Retry-After"); const wait=ra?parseInt(ra,10)*1000:(backoffMs*Math.pow(2,i)); await new Promise(r=>setTimeout(r,Math.min(wait,8000))); }
    throw new Error("Too many requests (429) after retries");
  }
  get(p){return this.request(p,{method:"GET"});} post(p,b){return this.request(p,{method:"POST",body:b});}
}
window.DJHttp = new HttpClient(location.pathname.replace(/[^\/]+$/, ""));
