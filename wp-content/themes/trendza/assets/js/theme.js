document.addEventListener('DOMContentLoaded',function(){const b=document.querySelector('.menu-toggle'),m=document.getElementById('mobile-menu');if(b&&m){b.addEventListener('click',function(){const o=b.getAttribute('aria-expanded')==='true';b.setAttribute('aria-expanded',String(!o));m.classList.toggle('is-open',!o);});}
const endpoint=window.trendzaAnalytics&&window.trendzaAnalytics.endpoint;
if(endpoint){document.querySelectorAll('[data-trendza-product]').forEach(function(el){const id=el.getAttribute('data-trendza-product');if(id){fetch(endpoint,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({event:'view',product_id:Number(id)}),keepalive:true}).catch(function(){});}});}
});
