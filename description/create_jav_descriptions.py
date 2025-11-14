# -*- coding: utf-8 -*-

import random
import os

# --- Daftar Kata (Disesuaikan untuk JAV) ---

# Kata kerja
actions = [
    "Watch", "Stream", "Download", "Find", "Get", "Explore", "Discover", "See", "Access", "Browse",
    "Enjoy", "Search", "View", "Get Now", "Download Now", "Stream Now", "Watch Now", "Find Now",
    "Explore Now", "Discover Now", "Access Now", "Browse Now", "Enjoy Now", "Search Now",
    "View Now", "Get Instantly", "Download Fast", "Stream Fast", "Watch Fast", "Find Fast",
    "Explore Fast", "Discover Fast", "Access Fast", "Browse Fast", "Enjoy Fast", "Search Fast",
    "View Fast", "Get Daily", "Download Daily", "Stream Daily", "Watch Daily", "Find Daily",
    "Explore Daily", "Discover Daily", "Access Daily", "Browse Daily", "Enjoy Daily",
    "Search Daily", "View Daily", "Get Free", "Download Free", "Stream Free", "Watch Free",
    "Find Free", "Explore Free", "Discover Free", "Access Free", "Browse Free", "Enjoy Free",
    "Search Free", "View Free", "Get Latest", "Download Latest", "Stream Latest", "Watch Latest",
    "Find Latest", "Explore Latest", "Discover Latest", "Access Latest", "Browse Latest",
    "Enjoy Latest", "Search Latest", "View Latest", "Get Full", "Download Full", "Stream Full",
    "Watch Full", "Find Full", "Explore Full", "Discover Full", "Access Full", "Browse Full",
    "Enjoy Full", "Search Full", "View Full"
]

# Tipe konten
content_types = [
    "JAV", "JAV movie", "JAV video", "full movie", "full video", "uncensored JAV", "JAV uncensored",
    "JAV sub English", "JAV subtitle English", "JAV with subtitles", "subtitled JAV",
    "actress video", "actress movie", "JAV actress", "new release", "latest video",
    "popular movie", "trending video", "uncensored clip", "subtitled video", "JAV full",
    "movie collection", "video collection", "actress collection", "uncensored collection",
    "subtitled collection", "JAV archive", "movie archive", "video archive",
    "actress archive", "uncensored archive", "subtitled archive", "JAV series",
    "movie series", "video series", "actress series", "uncensored series", "subtitled series",
    "JAV database", "movie database", "video database", "actress database",
    "uncensored database", "subtitled database", "JAV portal", "movie portal",
    "video portal", "actress portal", "uncensored portal", "subtitled portal",
    "JAV library", "movie library", "video library", "actress library",
    "uncensored library", "subtitled library", "JAV source", "movie source",
    "video source", "actress source", "uncensored source", "subtitled source",
    "JAV stream", "movie stream", "video stream", "actress stream", "uncensored stream",
    "subtitled stream", "JAV download", "movie download", "video download",
    "actress download", "uncensored download", "subtitled download", "JAV update",
    "movie update", "video update", "actress update", "uncensored update",
    "subtitled update", "JAV daily", "movie daily", "video daily", "actress daily",
    "uncensored daily", "subtitled daily", "JAV free", "movie free", "video free"
]

# Kualitas/Sifat
qualities = [
    "Full HD", "1080p", "720p", "HD", "High Quality", "Best Quality", "Premium", "Exclusive",
    "Latest", "New", "Popular", "Trending", "Top Rated", "Uncensored", "Complete",
    "Full Version", "High Speed", "Fast", "Reliable", "Daily Update", "Updated",
    "High Definition", "HD Quality", "Premium Quality", "Exclusive Content",
    "Latest Release", "New Update", "Popular Choice", "Trending Now", "Top Rated Video",
    "Fully Uncensored", "Complete Collection", "Full Access", "High Speed Download",
    "Fast Streaming", "Reliable Source", "Daily Updates", "Frequently Updated",
    "HD Streaming", "Premium Access", "Exclusive Video", "Latest Movie",
    "New Collection", "Popular Actress", "Trending Series", "Top Rated Site",
    "Uncensored Version", "Complete Archive", "Full Library", "High Speed Access",
    "Fast Server", "Reliable Stream", "Daily Content", "Always Updated",
    "HD Download", "Premium Collection", "Exclusive Access", "Latest Update",
    "New Video", "Popular Stream", "Trending Uncensored", "Top Rated Actress",
    "Uncensored Access", "Complete Database", "Full Download", "High Speed Server",
    "Fast Access", "Reliable Download", "Daily Uploads", "Constantly Updated",
    "HD Access", "Premium Database", "Exclusive Update", "Latest Stream",
    "New Actress", "Popular Download", "Trending Subtitled", "Top Rated Movie",
    "Uncensored Download", "Complete Stream", "Full Movie HD", "High Speed Stream",
    "Fast Update", "Reliable Access", "Daily New", "Regularly Updated"
]

# Fitur
features = [
    "English Subtitles", "Sub English", "Subtitle English", "Indonesian Subtitles",
    "Sub Indo", "Uncensored", "Full Uncensored", "No Censor", "Direct Download",
    "Streaming Online", "Free Stream", "Free Download", "Actress Details",
    "Actress Profile", "Full Duration", "No Ads", "Mobile Friendly", "Daily Updates",
    "New Videos Daily", "Multi-Language", "HD Available", "4K Available",
    "English Sub", "Indo Sub", "Uncensored Version", "Direct Link",
    "Online Streaming", "Free Access", "Free Full Movie", "Actress Database",
    "Actress Info", "Full Length", "Ad-Free", "Mobile Optimized", "Updated Daily",
    "New Content Daily", "Multiple Subtitles", "HD Quality", "4K Quality",
    "Subtitled English", "Subtitled Indonesian", "Uncensored Scenes",
    "Direct Download Link", "Stream Online Free", "Free Streaming", "Free Download Access",
    "Actress Library", "Actress Bio", "Full Movie", "No Advertisements",
    "Responsive Design", "Updated Frequently", "New Releases Daily",
    "Dual Subtitles", "HD Video", "4K Video", "English Subs", "Indonesian Subs",
    "Uncensored Full", "Direct Download Links", "Stream Online HD", "Free Access Portal",
    "Free Full Videos", "Actress Directory", "Actress Gallery", "Full Episodes",
    "No Popups", "Mobile View", "Updated Regularly", "New Videos Added Daily",
    "Multiple Languages", "HD Download", "4K Download", "Subbed English",
    "Subbed Indonesian", "Uncensored Only", "Fast Download Links",
    "Stream Online Full", "Free Membership", "Free Premium", "Actress List",
    "Actress Details", "Full Series", "Ad-Free Streaming", "Tablet Friendly",
    "Updated Hourly", "New Content Added", "Multi-Subtitles", "HD Stream", "4K Stream"
]

# Keuntungan
benefits = [
    "free access", "fast streaming", "easy download", "no ads", "daily updates",
    "complete collection", "mobile friendly", "high quality", "uncensored content",
    "subtitled videos", "actress library", "latest releases", "secure browsing",
    "no registration", "premium access", "fast servers", "simple navigation",
    "ad-free experience", "new content daily", "full archive", "mobile optimized",
    "HD quality", "uncensored access", "English subtitles", "actress database",
    "new movies", "safe and secure", "no signup", "premium content",
    "high-speed servers", "easy to use", "no popups", "updated daily",
    "massive collection", "tablet friendly", "best quality", "all uncensored",
    "Indonesian subtitles", "actress profiles", "exclusive releases", "private browsing",
    "no fees", "VIP access", "dedicated servers", "user-friendly interface",
    "no interruptions", "always updated", "huge library", "responsive design",
    "top quality", "100% uncensored", "multi-language subs", "actress details",
    "trending movies", "anonymous browsing", "no cost", "full access",
    "lightning fast", "clean design", "no distractions", "constantly updated",
    "complete database", "cross-platform", "4K quality", "uncensored library",
    "all subtitles", "actress info", "popular series", "encrypted connection",
    "free forever", "VIP content", "fast downloads", "minimal ads",
    "fresh content", "full movie database", "works on all devices", "UHD quality",
    "uncensored database", "subtitle options", "actress bios", "hot releases",
    "secure access", "completely free"
]

# Platform/Tempat
platforms = [
    "website", "site", "platform", "portal", "database", "archive", "library", "collection",
    "streaming site", "video portal", "actress database", "JAV library", "uncensored site",
    "subtitle site", "video collection", "online archive", "streaming platform",
    "video database", "actress archive", "JAV portal", "uncensored archive",
    "subtitle database", "movie collection", "web archive", "streaming service",
    "video archive", "actress portal", "JAV database", "uncensored database",
    "subtitle archive", "film library", "digital archive", "streaming portal",
    "video library", "actress library", "JAV collection", "uncensored collection",
    "subtitle collection", "movie database", "media archive", "streaming website",
    "video platform", "actress directory", "JAV archive", "uncensored library",
    "subtitle library", "movie archive", "media library", "streaming hub",
    "video database", "actress gallery", "JAV hub", "uncensored portal",
    "subtitle portal", "movie portal", "media database", "streaming archive",
    "video source", "actress info", "JAV source", "uncensored source",
    "subtitle source", "movie source", "media platform", "streaming library",
    "video directory", "actress list", "JAV directory", "uncensored directory",
    "subtitle directory", "movie directory", "media source", "streaming directory",
    "video hub", "actress website", "JAV website", "uncensored website",
    "subtitle website", "movie website", "media hub", "streaming collection",
    "video collection", "actress site", "JAV site", "uncensored site",
    "subtitle site", "movie site", "media site"
]

# --- Template Kalimat (Tidak diubah, hanya menggunakan kata-kata di atas) ---
templates = [
    "{action} {content_type} with {quality} performance and {feature}.",
    "{action} {content_type} via {platform} with {feature} and {benefit}.",
    "{action} {content_type} with {feature} and {quality} performance.",
    "{action} {content_type} on {platform} supporting {feature} and {benefit}.",
    "{action} {content_type} with {quality} via {platform} for {benefit}.",
    "{action} {content_type} for {benefit} with {feature}.",
    "{action} {content_type} on {platform} with {quality} performance and {benefit}.",
    "{action} {content_type} offering {feature} and {benefit}.",
    "{action} {content_type} with {quality} and {feature} for {benefit}.",
    "{action} {content_type} on {platform} for {benefit} and {feature}.",
    "{action} {content_type} with {feature} for {benefit} on {platform}.",
    "{action} {content_type} via {platform} with {quality} and {feature}.",
    "{action} {content_type} supporting {feature} for {benefit}.",
    "{action} {content_type} with {quality} performance on {platform} for {benefit}.",
    "{action} {content_type} via {platform} for {benefit} with {feature}.",
    "{action} {content_type} with {feature} and {benefit} on {platform}.",
    "{action} {content_type} on {platform} with {feature} for {benefit}.",
    "{action} {content_type} offering {quality} performance and {feature}.",
    "{action} {content_type} with {benefit} via {platform} with {feature}.",
    "{action} {content_type} on {platform} for {feature} and {benefit}.",
    "{action} {content_type} with {quality} performance and {benefit} on {platform}.",
    "{action} {content_type} via {platform} with {feature} for {benefit}.",
    "{action} {content_type} supporting {quality} and {feature} on {platform}.",
    "{action} {content_type} with {feature} for {quality} performance and {benefit}.",
    "{action} {content_type} on {platform} with {benefit} and {feature}.",
    "{action} {content_type} via {platform} for {quality} performance and {benefit}.",
    "{action} {content_type} with {feature} and {quality} performance for {benefit}.",
    "{action} {content_type} on {platform} offering {feature} and {benefit}.",
    "{action} {content_type} with {benefit} and {feature} via {platform}.",
    "{action} {content_type} for {feature} with {quality} performance on {platform}.",
    "{action} {content_type} via {platform} with {benefit} and {quality} performance.",
    "{action} {content_type} supporting {feature} and {benefit} on {platform}.",
    "{action} {content_type} with {quality} performance for {benefit} and {feature}.",
    "{action} {content_type} on {platform} with {feature} and {quality} performance.",
    "{action} {content_type} via {platform} for {feature} with {benefit}.",
    "{action} {content_type} with {benefit} and {quality} performance on {platform}.",
    "{action} {content_type} offering {feature} for {benefit} on {platform}.",
    "{action} {content_type} with {feature} and {benefit} for {quality} performance.",
    "{action} {content_type} on {platform} for {quality} performance with {feature}.",
    "{action} {content_type} via {platform} with {feature} and clear {benefit}.",
    "{action} {content_type} with {quality} performance and {feature} for {benefit}.",
    "{action} {content_type} on {platform} supporting {benefit} and {feature}.",
    "{action} {content_type} for {benefit} with {quality} performance on {platform}.",
    "{action} {content_type} via {platform} for {feature} and {quality} performance.",
    "{action} {content_type} with {feature} for {benefit} with {quality} performance.",
    "{action} {content_type} on {platform} with {benefit} for {feature}.",
    "{action} {content_type} offering {quality} performance for {benefit}.",
    "{action} {content_type} with {feature} and clear {benefit} via {platform}.",
    "{action} {content_type} for {quality} performance with {feature} on {platform}.",
    "{action} {content_type} via {platform} with {benefit} for {feature}.",
    "{action} {content_type} with {quality} performance and {benefit} for {feature}.",
    "{action} {content_type} on {platform} for {benefit} with {quality} performance.",
    "{action} {content_type} supporting {feature} with {benefit} on {platform}.",
    "{action} {content_type} with {feature} for {quality} performance on {platform}.",
    "{action} {content_type} via {platform} for clear {benefit} and {feature}.",
    "{action} {content_type} with {benefit} and {feature} for {quality} performance.",
    "{action} {content_type} on {platform} with {quality} performance for {benefit}.",
    "{action} {content_type} offering {feature} and {quality} performance on {platform}.",
    "{action} {content_type} with {feature} and {benefit} for clear {platform}.",
    "{action} {content_type} for {benefit} with {feature} and {quality} performance.",
    "{action} {content_type} via {platform} with {feature} for {quality} performance.",
    "{action} {content_type} with {quality} performance and {feature} on clear {platform}.",
    "{action} {content_type} on {platform} for {feature} with clear {benefit}.",
    "{action} {content_type} supporting {benefit} and {quality} performance on {platform}.",
    "{action} {content_type} with {feature} for clear {benefit} via {platform}.",
    "{action} {content_type} for {quality} performance and {benefit} on {platform}.",
    "{action} {content_type} via {platform} with {quality} performance for {feature}.",
    "{action} {content_type} with clear {benefit} and {feature} on {platform}.",
    "{action} {content_type} offering {benefit} for {feature} on {platform}.",
    "{action} {content_type} with {quality} performance for {feature} and {benefit}.",
    "{action} {content_type} on {platform} with {feature} for {quality} performance.",
    "{action} {content_type} via {platform} for {quality} performance with {benefit}.",
    "{action} {content_type} with {feature} and {quality} performance on clear {platform}.",
    "{action} {content_type} supporting {feature} for {quality} performance on {platform}.",
    "{action} {content_type} with {benefit} for {feature} and {quality} performance.",
    "{action} {content_type} on {platform} for clear {benefit} and {quality} performance.",
    "{action} {content_type} via {platform} with {feature} and premium {benefit}.",
    "{action} {content_type} with {quality} performance and {benefit} on premium {platform}.",
    "{action} {content_type} offering {feature} for {quality} performance on {platform}.",
    "{action} {content_type} with {feature} and {benefit} for premium {platform}.",
    "{action} {content_type} for {benefit} with {quality} performance and {feature}.",
    "{action} {content_type} via {platform} for {feature} and premium {benefit}.",
    "{action} {content_type} with {quality} performance for clear {benefit} on {platform}.",
    "{action} {content_type} on {platform} with {feature} and premium {quality} performance.",
    "{action} {content_type} supporting clear {benefit} for {feature} on {platform}.",
    "{action} {content_type} with {feature} for {quality} performance and premium {benefit}.",
    "{action} {content_type} via {platform} with {benefit} for {quality} performance.",
    "{action} {content_type} with {quality} performance and {feature} for clear {benefit}.",
    "{action} {content_type} on {platform} for {feature} with premium {quality} performance.",
    "{action} {content_type} offering {benefit} and {feature} for {quality} performance.",
    "{action} {content_type} with {feature} and {quality} performance for premium {benefit}.",
    "{action} {content_type} via {platform} for {quality} performance and clear {feature}.",
    "{action} {content_type} with {benefit} and {feature} on premium {platform}.",
    "{action} {content_type} supporting {feature} and {benefit} for {quality} performance.",
    "{action} {content_type} with {quality} performance for {feature} on clear {platform}.",
    "{action} {content_type} on {platform} with {benefit} and {feature} for {quality} performance."
]

def generate_description():
    template = random.choice(templates)
    
    # Mengganti 'performance' di template agar lebih relevan (opsional, tapi bagus)
    template = template.replace("performance", "quality")
    
    description = template.format(
        action=random.choice(actions),
        content_type=random.choice(content_types),
        quality=random.choice(qualities),
        feature=random.choice(features),
        benefit=random.choice(benefits),
        platform=random.choice(platforms)
    )
    # Memastikan panjang 10-20 kata
    words = description.split()
    if len(words) > 20:
        description = " ".join(words[:20])
    elif len(words) < 10:
        # Menambah kata jika terlalu pendek
        description += f" on {random.choice(platforms)} with {random.choice(benefits)}."
        words = description.split()
        if len(words) > 20:
             description = " ".join(words[:20])
             
    return description.strip().replace("..", ".").replace(" .", ".")

def generate_unique_descriptions(num_descriptions):
    descriptions = set()
    while len(descriptions) < num_descriptions:
        desc = generate_description()
        # Memastikan unik dan panjangnya sesuai
        if 10 <= len(desc.split()) <= 20 and desc not in descriptions:
            descriptions.add(desc)
    return list(descriptions)

def save_descriptions(descriptions, output_file="jav_descriptions.txt"):
    with open(output_file, "w", encoding="utf-8") as f:
        for desc in descriptions:
            f.write(desc + "\n")
    print(f"Successfully saved {len(descriptions)} descriptions to {output_file}")

if __name__ == "__main__":
    # Menghasilkan 10000 deskripsi
    num_descriptions = 40000
    descriptions = generate_unique_descriptions(num_descriptions)
    save_descriptions(descriptions, "jav_descriptions.txt")
