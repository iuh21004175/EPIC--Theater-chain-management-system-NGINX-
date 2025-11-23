document.addEventListener('DOMContentLoaded', function() {
    const urlMinio = "{{ $_ENV['MINIO_SERVER_URL'] }}"; 
    const baseUrl = "{{ $_ENV['URL_WEB_BASE'] }}"; 
    const trailerModal = document.getElementById("trailerModal");
    const closeModal = document.getElementById("closeModal");
    const trailerIframe = document.getElementById("trailerIframe");
    const rapSelect = document.getElementById('rapSelect');
    const dayTabs = document.getElementById('dayTabs');
    const nextBtn = document.getElementById('nextDay');
    const prevBtn = document.getElementById('prevDay');

    const stars = document.querySelectorAll('#starRating button');
    const ratingValue = document.getElementById('ratingValue');
    let currentRating = 5; // mặc định 5 sao

    let startDate = new Date();
    let visibleDays = 7;
    let currentStartIndex = 0;
    let allDays = [];
    let activeIndex = 0;

    for (let i = 0; i < 30; i++) {
        let d = new Date(startDate);
        d.setDate(d.getDate() + i);
        allDays.push(d);
    }

    // ===== Trailer =====
    function openTrailer(url) {
        trailerIframe.src = url + (url.includes("?") ? "&" : "?") + "autoplay=1";
        trailerModal.classList.remove("hidden");
    }

    document.querySelectorAll(".trailer-btn").forEach(btn => {
        btn.addEventListener("click", () => openTrailer(btn.dataset.url));
    });

    closeModal.addEventListener("click", () => {
        trailerModal.classList.add("hidden");
        trailerIframe.src = "";
    });

    trailerModal.addEventListener("click", e => {
        if (e.target === trailerModal) {
            trailerModal.classList.add("hidden");
            trailerIframe.src = "";
        }
    });

    function youtubeEmbed(url) {
        if (!url) return "";
        const regex = /(?:youtube\.com\/(?:.*v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/;
        const match = url.match(regex);
        return match && match[1] ? "https://www.youtube.com/embed/" + match[1] : url;
    }

    // ===== Rating =====
    function updateStars(rating) {
        stars.forEach(star => {
            if(star.dataset.value <= rating){
                star.classList.add('text-yellow-400');
                star.classList.remove('text-gray-300');
            } else {
                star.classList.add('text-gray-300');
                star.classList.remove('text-yellow-400');
            }
        });
    }

    stars.forEach(star => {
        star.addEventListener('click', () => {
            currentRating = star.dataset.value;
            ratingValue.textContent = currentRating;
            updateStars(currentRating);
        });
    });
    updateStars(currentRating);

    // ===== Day Tabs =====
    function formatDate(d){ return ("0"+d.getDate()).slice(-2)+"/"+("0"+(d.getMonth()+1)).slice(-2); }
    function formatWeekday(d){ const w=["CN","T2","T3","T4","T5","T6","T7"]; return w[d.getDay()]; }

    function renderDays(){
        dayTabs.innerHTML = '';
        for(let i=currentStartIndex;i<currentStartIndex+visibleDays;i++){
            if(!allDays[i]) continue;
            const btn=document.createElement('button');
            btn.className='flex-shrink-0 text-center px-4 py-2 rounded-lg border border-gray-300 font-semibold text-gray-700 hover:bg-red-500 hover:text-white transition-colors';
            btn.innerHTML=`${formatWeekday(allDays[i])}<br>${formatDate(allDays[i])}`;
            btn.dataset.index=i;
            if(i===activeIndex){
                btn.classList.add('bg-red-600','text-white');
                btn.classList.remove('text-gray-700','border-gray-300');
            }
            dayTabs.appendChild(btn);
        }
    }

    dayTabs.addEventListener('click', function(e){
        const btn = e.target.closest('button');
        if(!btn) return;
        dayTabs.querySelectorAll('button').forEach(b=>{
            b.classList.remove('bg-red-600','text-white');
            b.classList.add('text-gray-700','border-gray-300');
        });
        btn.classList.add('bg-red-600','text-white');
        btn.classList.remove('text-gray-700','border-gray-300');
        activeIndex=parseInt(btn.dataset.index);
        const selectedDate = allDays[activeIndex];
        console.log("Ngày được chọn:", selectedDate.toISOString().split('T')[0]);
        // TODO: fetch suất chiếu theo selectedDate
    });

    nextBtn.addEventListener('click', ()=>{
        if(currentStartIndex+visibleDays<allDays.length){
            currentStartIndex++;
            renderDays();
        }
    });
    prevBtn.addEventListener('click', ()=>{
        if(currentStartIndex>0){
            currentStartIndex--;
            renderDays();
        }
    });
    renderDays();

    // ===== Load rạp =====
    if(rapSelect){
        fetch(baseUrl + "/api/rap-phim-khach")
        .then(res=>res.json())
        .then(data=>{
            if(rapSelect){
                rapSelect.innerHTML='<option value="">Chọn rạp</option>';
            }
            if(data.success && data.data.length>0){
                data.data.forEach(rap=>{
                    const option=document.createElement("option");
                    option.value=rap.ten; option.textContent=rap.ten;
                    if(rapSelect) rapSelect.appendChild(option);
                });
            }else{
                if(rapSelect) rapSelect.innerHTML='<option value="">Không có rạp nào</option>';
            }
        })
        .catch(err=>{
            console.error("Lỗi load rạp:",err);
            if(rapSelect) rapSelect.innerHTML='<option value="">Lỗi tải rạp</option>';
        });
    }

    // ===== Comment & Reply =====
    document.querySelectorAll('.replyBtn').forEach(btn=>{
        btn.addEventListener('click',()=>{
            const form = btn.nextElementSibling;
            form.classList.toggle('hidden');
        });
    });

    document.querySelectorAll('.replyForm').forEach(form=>{
        form.addEventListener('submit', e=>{
            e.preventDefault();
            const textarea=form.querySelector('textarea');
            if(textarea.value.trim()==='') return;
            const replyDiv=document.createElement('div');
            replyDiv.className='bg-gray-100 p-2 rounded-lg text-gray-700';
            replyDiv.textContent=textarea.value;
            form.parentElement.querySelector('.replies').appendChild(replyDiv);
            textarea.value='';
            form.classList.add('hidden');
        });
    });

    // ===== Load phim =====
    function loadThongTinPhim(phim){
        const html = `
        <div class="relative w-full h-72 md:h-80 lg:h-96 bg-black">
            <img src="${urlMinio}/${phim.poster_url}" alt="${phim.ten_phim}" class="w-full h-full object-cover opacity-70">
            <div class="absolute inset-0 poster-overlay"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <button type="button" 
                        data-url="${youtubeEmbed(phim.trailer_url)}"
                        class="trailer-btn flex items-center justify-center w-[320px] h-[100px]  rounded-lg text-white font-semibold px-4 py-2 text-sm transition-all duration-300">
                         <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle-play" 
                            class="w-12 h-12 mr-3" role="img" xmlns="http://www.w3.org/2000/svg" 
                            viewBox="0 0 512 512">
                            <path fill="currentColor" 
                                d="M0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zM188.3 
                                147.1c-7.6 4.2-12.3 12.3-12.3 20.9V344c0 8.7 
                                4.7 16.7 12.3 20.9s16.8 4.1 24.3-.5l144-88c7.1-4.4 
                                11.5-12.1 11.5-20.5s-4.4-16.1-11.5-20.5l-144-88c-7.4-4.5 
                                -16.7-4.7-24.3-.5z"></path>
                        </svg>
                </button>
            </div>
        </div>
        <div class="container mx-auto max-w-4xl px-4 mt-6 relative">
            <div class="flex flex-col md:flex-row gap-8">
                <div class="w-full md:w-1/3 flex-shrink-0 -mt-16 md:-mt-24">
                    <img src="${urlMinio}/${phim.poster_url}"  alt="${phim.ten_phim}" class="w-full rounded-xl shadow-lg">
                </div>
                <div class="w-full md:w-2/3 bg-white rounded-xl shadow-lg p-6">
                    <h1 class="text-3xl md:text-4xl font-bold flex items-center gap-2">
                        ${phim.ten_phim} <span class="text-sm px-2 py-1 bg-red-600 text-white font-bold rounded">${phim.do_tuoi}</span>
                    </h1>
                    <div class="text-gray-600 mt-1 text-sm md:text-base">
                        <span class="mr-4"><strong>Thời lượng:</strong> ${phim.thoi_luong} phút</span>
                        <span><strong>Khởi chiếu:</strong> ${new Date(phim.ngay_cong_chieu).toLocaleDateString("vi-VN")}</span>
                    </div>
                    <div class="flex items-center mt-2">
                            
                            <svg class="w-5 h-5 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 576 512">
                                <path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/>
                            </svg>
                            <span class="text-gray-800 font-semibold text-sm md:text-base">4.6 (300 votes)</span>
                        </div>
                    <div class="text-sm text-gray-700 space-y-1 mt-2">
                        <p><strong>Quốc gia:</strong> ${phim.quoc_gia}</p>
                        <p><strong>Thể loại:</strong> ${phim.the_loai.map(item => item.the_loai.ten).join(", ")}</p>
                        <p><strong>Đạo diễn:</strong> ${phim.dao_dien}</p>
                        <p><strong>Diễn viên:</strong> ${phim.dien_vien}</p>
                    </div>
                </div>
            </div>
        </div>
    `;
        document.getElementById('thongTinPhim').innerHTML=html;
        document.querySelectorAll(".trailer-btn").forEach(btn=>{
            btn.addEventListener("click",()=>{if(btn.dataset.url) openTrailer(btn.dataset.url)});
        });
    }

    function loadNoiDungPhim(phim){
        const html =`
        <div class="w-full max-w-screen-xl mx-auto bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center mb-2">
                <div class="w-1 h-6 bg-red-600 mr-2"></div>
                <h3 class="text-xl font-bold">Nội dung phim</h3>
            </div>
            <p class="text-gray-700">
                ${phim.mo_ta}
            </p>
        </div>
        `;
        document.getElementById('noiDungPhim').innerHTML=html;
    }

    const pathParts=window.location.pathname.split("/");
    const slugWithId=pathParts[pathParts.length-1];
    const idPhim=slugWithId.split("-").pop();

    fetch(`${baseUrl}/api/dat-ve/${idPhim}`)
        .then(res=>res.json())
        .then(data=>{
            if(data.success && data.data){
                loadThongTinPhim(data.data);
                loadNoiDungPhim(data.data);
            }
        })
        .catch(err=>console.error("Lỗi load phim:",err));
});
s