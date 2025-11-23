async function loadBanners(url) {
    let banners = [];
    await fetch(`${url}/api/banner/side-show`)
        .then(response => response.json())
        .then(data => {
            banners = data.data;
        })
        .catch(error => {
            console.error('Error fetching banners:', error);
        });
    return new Promise((resolve) => { resolve(banners); });
}
document.addEventListener('DOMContentLoaded', async function() { 
    const bannerList = document.getElementById('bannerContainer');
    let banners = await loadBanners(bannerList.dataset.url);
    if(banners.length > 0) {
        bannerList.innerHTML = ''; // Clear existing content if banners are available
    }
    banners.forEach(banner => {
        const img = document.createElement('img');
        img.src = `${bannerList.dataset.urlminio}/${banner.anh_url}`; // Assuming banner object has an imageUrl property
        img.classList.add('w-full', 'h-full', 'object-cover', 'flex-shrink-0');
        bannerList.appendChild(img);
    });
})