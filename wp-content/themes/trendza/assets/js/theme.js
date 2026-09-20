document.addEventListener('DOMContentLoaded',function(){
    const b=document.querySelector('.menu-toggle'),m=document.getElementById('mobile-menu');
    if(b&&m){b.addEventListener('click',function(){const o=b.getAttribute('aria-expanded')==='true';const next=!o;b.setAttribute('aria-expanded',String(next));b.setAttribute('aria-label',next?'Close menu':'Open menu');m.hidden=!next;m.classList.toggle('is-open',next);});}

    const config=window.trendzaConfig||{};
    const endpoint=config.analyticsEndpoint;

    function track(event,productId,query){
        if(!endpoint)return;
        const payload={event:event,product_id:Number(productId)||0};
        if(query)payload.query=query;
        fetch(endpoint,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload),keepalive:true}).catch(function(){});
    }

    if(endpoint){
        const search=document.querySelector('[data-trendza-search]');
        const query=search?search.getAttribute('data-trendza-search'):'';

        if(search){
            track('search',0,query);
        }

        document.querySelectorAll('[data-trendza-product]').forEach(function(el){
            const id=el.getAttribute('data-trendza-product');
            if(id)track('view',id);
        });

        document.querySelectorAll('a.add_to_cart_button, button.single_add_to_cart_button').forEach(function(button){
            button.addEventListener('click',function(){
                const id=button.getAttribute('data-product_id')||config.productId||0;
                track('add_to_cart',id);
            });
        });

        if(config.isCheckout){
            track('begin_checkout',0);
        }
    }

    const recommendationRoot=document.querySelector('[data-trendza-recommendations]');
    const aiEndpoint=config.aiEndpoint;
    if(recommendationRoot&&aiEndpoint){
        const id=recommendationRoot.getAttribute('data-trendza-recommendations');
        const grid=recommendationRoot.querySelector('[data-recommendation-grid]');
        if(id&&grid){
            fetch(aiEndpoint+'/recommendations/'+encodeURIComponent(id)+'?limit=6',{headers:{'Accept':'application/json'}})
                .then(function(response){if(!response.ok)throw new Error('Recommendation request failed');return response.json();})
                .then(function(data){
                    const items=Array.isArray(data.recommendations)?data.recommendations:[];
                    if(!items.length){recommendationRoot.remove();return;}
                    grid.innerHTML=items.map(function(item){
                        const image=item.image?'<img loading="lazy" src="'+escapeHtml(item.image)+'" alt="'+escapeHtml(item.name||'')+'">':'';
                        const trend=item.trend_score?'<span class="trend-badge">'+escapeHtml((item.trend_status||'').replace(/^./,function(c){return c.toUpperCase();}))+' · '+escapeHtml(Math.round(item.trend_score))+'</span>':'';
                        const reason=item.why_recommended?'<p class="recommendation-reason">'+escapeHtml(item.why_recommended)+'</p>':'';
                        return '<article class="product-card recommendation-card" data-trendza-product="'+escapeHtml(item.id||0)+'">'+trend+'<a class="product-image" href="'+escapeHtml(item.url||'#')+'" aria-label="'+escapeHtml(item.name||'')+'">'+image+'</a><div class="product-body"><a class="product-title" href="'+escapeHtml(item.url||'#')+'">'+escapeHtml(item.name||'Product')+'</a><div class="product-price">'+formatPrice(item.price,item.currency)+'</div>'+reason+'</div></article>';
                    }).join('');

                    grid.querySelectorAll('[data-trendza-product]').forEach(function(el){
                        const productId=el.getAttribute('data-trendza-product');
                        if(productId)track('view',productId);
                    });
                }).catch(function(){recommendationRoot.remove();});
        }
    }

    function escapeHtml(value){return String(value==null?'':value).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];});}
    function formatPrice(value,currency){const number=Number(value);if(!Number.isFinite(number))return '';try{return new Intl.NumberFormat(undefined,{style:'currency',currency:currency||'ZAR',maximumFractionDigits:2}).format(number);}catch(e){return (currency||'ZAR')+' '+number.toFixed(2);}}
});
