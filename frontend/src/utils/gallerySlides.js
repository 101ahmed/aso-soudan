/**
 * Flatten albums (+ nested media) into carousel slides.
 * Falls back to album cover when media list is empty.
 */
export function albumsToSlides(albums = [], locale = 'fr') {
  const slides = []

  for (const album of albums || []) {
    const albumTitle =
      album.title?.[locale] ||
      album.title?.fr ||
      album.title?.ar ||
      album.title_fr ||
      album.title_ar ||
      ''

    const media = album.media || []
    if (media.length) {
      for (const m of media) {
        const src = m.url || m.src
        if (!src) continue
        slides.push({
          src,
          title: albumTitle,
          caption:
            (locale === 'ar' ? m.caption_ar : m.caption_fr) ||
            m.caption ||
            albumTitle,
          albumSlug: album.slug || (album.id != null ? String(album.id) : null),
        })
      }
      continue
    }

    const cover = album.cover || album.cover_url
    if (cover) {
      slides.push({
        src: cover,
        title: albumTitle,
        caption: albumTitle,
        albumSlug: album.slug || (album.id != null ? String(album.id) : null),
      })
    }
  }

  return slides
}

export function imagesToSlides(images = [], title = '') {
  return (images || [])
    .map((item) => {
      if (typeof item === 'string') {
        return { src: item, title, caption: title }
      }
      return {
        src: item.src || item.url || item.image || item.cover,
        title: item.title || title,
        caption: item.caption || item.title || title,
        albumSlug: item.albumSlug || item.slug || null,
      }
    })
    .filter((s) => s.src)
}
