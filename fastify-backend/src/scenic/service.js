'use strict'

const https = require('https')

function httpGet(url) {
  return new Promise((resolve, reject) => {
    https.get(url, (res) => {
      let data = ''
      res.on('data', chunk => data += chunk)
      res.on('end', () => {
        try {
          resolve(JSON.parse(data))
        } catch (e) {
          reject(new Error('JSON parse error: ' + e.message))
        }
      })
    }).on('error', reject)
  })
}

/**
 * 搜索指定地区的景区列表（分页）
 */
async function searchScenicSpots(region, pageNum = 0) {
  const ak = process.env.BAIDU_MAP_AK
  if (!ak) throw new Error('BAIDU_MAP_AK not configured')

  const params = new URLSearchParams({
    query: '景区',
    region,
    tag: '旅游景点',
    scope: '2',
    page_size: '20',
    page_num: String(pageNum),
    output: 'json',
    ak,
  })

  const url = `https://api.map.baidu.com/place/v2/search?${params}`
  return httpGet(url)
}

/**
 * 收集指定地区所有景区（自动翻页，最多20页）
 */
async function collectAllSpots(region) {
  const allSpots = []
  let page = 0

  while (true) {
    const data = await searchScenicSpots(region, page)

    if (data.status !== 0) {
      throw new Error(`百度API错误: ${data.message}`)
    }

    const results = data.results || []
    if (results.length === 0) break

    allSpots.push(...results)

    if (results.length < 20 || page >= 19) break
    page++

    await new Promise(r => setTimeout(r, 300))
  }

  return allSpots
}

/**
 * 按名称搜索主景区，获取坐标
 */
async function findMainSpot(name, region) {
  const ak = process.env.BAIDU_MAP_AK
  if (!ak) throw new Error('BAIDU_MAP_AK not configured')

  const params = new URLSearchParams({
    query: name,
    region,
    scope: '2',
    page_size: '5',
    output: 'json',
    ak,
  })

  const url = `https://api.map.baidu.com/place/v2/search?${params}`
  return httpGet(url)
}

/**
 * 在指定坐标周边圆形范围内搜索景点
 */
async function searchNearby(lat, lng, radius, keyword, pageNum = 0) {
  const ak = process.env.BAIDU_MAP_AK
  if (!ak) throw new Error('BAIDU_MAP_AK not configured')

  const params = new URLSearchParams({
    query: keyword,
    location: `${lat},${lng}`,
    radius: String(radius),
    scope: '2',
    page_size: '20',
    page_num: String(pageNum),
    output: 'json',
    ak,
  })

  const url = `https://api.map.baidu.com/place/v2/search?${params}`
  return httpGet(url)
}

/**
 * 收集景区周边所有子景点（多关键词去重）
 */
async function collectSubSpots(name, region, radius = 5000, keywords = ['景点', '寺庙', '佛殿', '参观', '景区']) {
  // 先找主景区坐标
  const mainResult = await findMainSpot(name, region)

  if (mainResult.status !== 0 || !mainResult.results?.length) {
    throw new Error(`未找到景区"${name}"：${mainResult.message || '无结果'}`)
  }

  const main = mainResult.results[0]
  const { lat, lng } = main.location

  // 多关键词搜索周边，合并去重
  const allSpots = new Map()

  for (const kw of keywords) {
    let page = 0
    while (true) {
      const data = await searchNearby(lat, lng, radius, kw, page)
      if (data.status !== 0 || !data.results?.length) break

      for (const s of data.results) {
        if (s.uid !== main.uid) {
          allSpots.set(s.uid, s)
        }
      }

      if (data.results.length < 20 || page >= 4) break
      page++
      await new Promise(r => setTimeout(r, 300))
    }
  }

  return {
    main: formatSpot(main),
    subSpots: [...allSpots.values()].map(formatSpot),
  }
}

function formatSpot(s) {
  const info = s.detail_info || {}
  return {
    uid: s.uid,
    name: s.name,
    address: s.address || null,
    telephone: s.telephone || null,
    location: s.location || null,
    overall_rating: info.overall_rating || null,
    tag: info.tag || null,
    description: info.description || null,
  }
}

/**
 * 多边形区域检索（v3/polygon）
 * bounds: "lat1,lng1,lat2,lng2,..." 全逗号分隔的平铺坐标串
 */
async function searchByPolygon(bounds, query = '景区', tag = '旅游景点', pageNum = 0) {
  const ak = process.env.BAIDU_MAP_AK
  if (!ak) throw new Error('BAIDU_MAP_AK not configured')

  const params = new URLSearchParams({
    query,
    bounds,
    tag,
    scope: '2',
    photo_show: 'true',
    page_size: '20',
    page_num: String(pageNum),
    output: 'json',
    ak,
  })

  return httpGet(`https://api.map.baidu.com/place/v3/polygon?${params}`)
}

/**
 * 批量地点详情检索（v3/detail），每批最多 10 个 uid
 * 返回 uid -> detail_info 映射
 */
async function fetchDetailMap(uids) {
  const ak = process.env.BAIDU_MAP_AK
  if (!ak) throw new Error('BAIDU_MAP_AK not configured')

  const detailMap = {}
  for (let i = 0; i < uids.length; i += 10) {
    const batch = uids.slice(i, i + 10)
    const params = new URLSearchParams({ uids: batch.join(','), scope: '2', output: 'json', ak })
    try {
      const data = await httpGet(`https://api.map.baidu.com/place/v3/detail?${params}`)
      if (data.status === 0 && data.results) {
        for (const r of data.results) {
          if (r.uid) detailMap[r.uid] = r.detail_info || {}
        }
      }
    } catch (_) { /* ignore single batch failure */ }
    if (i + 10 < uids.length) await new Promise(r => setTimeout(r, 300))
  }
  return detailMap
}

/**
 * 收集多边形区域内所有景点并补充详情（自动翻页）
 */
async function collectPolygonSpots(bounds, query = '景区', tag = '旅游景点') {
  const allSpots = []
  let page = 0

  while (true) {
    const data = await searchByPolygon(bounds, query, tag, page)
    if (data.status !== 0) throw new Error(`百度API错误: ${data.message || data.status}`)

    const results = data.results || []
    if (results.length === 0) break

    allSpots.push(...results)
    if (results.length < 20 || page >= 19) break
    page++
    await new Promise(r => setTimeout(r, 300))
  }

  // 批量拉取详情（补充 best_time / sug_time / description）
  const uids = allSpots.map(s => s.uid).filter(Boolean)
  const detailMap = await fetchDetailMap(uids)

  return allSpots.map(s => formatPolygonSpot(s, detailMap[s.uid] || {}))
}

function formatPolygonSpot(s, extraDetail = {}) {
  const info = s.detail_info || {}
  const merged = { ...info, ...extraDetail }

  return {
    uid:            s.uid,
    name:           s.name || null,
    tag:            merged.classified_poi_tag || merged.tag || null,
    overall_rating: merged.overall_rating || null,
    address:        s.address || null,
    location:       s.location || null,
    telephone:      s.telephone || null,
    street_id:      s.street_id || null,
    alias:          merged.new_alias || null,
    shop_hours:     merged.shop_hours || null,
    price:          merged.price || null,
    best_time:      merged.best_time || null,
    sug_time:       merged.sug_time || null,
    description:    merged.description || null,
    photos:         (s.photos || []).map(p => p.url || p).filter(url => typeof url === 'string'),
    status:         s.status || null,
  }
}

/**
 * 地点详情检索（v3/detail）
 * uids: 字符串数组，每批最多10个，自动分批
 * 返回完整 result 对象数组（含 detail_info）
 */
async function getDetailByUids(uids) {
  const ak = process.env.BAIDU_MAP_AK
  if (!ak) throw new Error('BAIDU_MAP_AK not configured')

  const results = []
  for (let i = 0; i < uids.length; i += 10) {
    const batch = uids.slice(i, i + 10)
    const params = new URLSearchParams({
      uids: batch.join(','),
      scope: '2',
      photo_show: 'true',
      output: 'json',
      ak,
    })
    const data = await httpGet(`https://api.map.baidu.com/place/v3/detail?${params}`)
    if (data.status !== 0) throw new Error(`百度API错误: ${data.message || data.status}`)
    results.push(...(data.results || []))
    if (i + 10 < uids.length) await new Promise(r => setTimeout(r, 300))
  }
  return results.map(formatDetailResult)
}

function formatDetailResult(s) {
  const info = s.detail_info || {}
  return {
    uid:            s.uid,
    name:           s.name || null,
    location:       s.location || null,
    province:       s.province || null,
    city:           s.city || null,
    area:           s.area || null,
    address:        s.address || null,
    status:         s.status || null,
    telephone:      s.telephone || null,
    street_id:      s.street_id || null,
    detail:         s.detail || null,
    detail_info: {
      tag:               info.tag || null,
      classified_poi_tag: info.classified_poi_tag || null,
      type:              info.type || null,
      detail_url:        info.detail_url || null,
      shop_hours:        info.shop_hours || null,
      price:             info.price || null,
      overall_rating:    info.overall_rating || null,
      image_num:         info.image_num || null,
      comment_num:       info.comment_num || null,
      brand:             info.brand || null,
      indoor_floor:      info.indoor_floor || null,
      ranking:           info.ranking || null,
      parent_id:         info.parent_id || null,
      best_time:         info.best_time || null,
      sug_time:          info.sug_time || null,
      description:       info.description || null,
      navi_location:     info.navi_location || null,
      photos:            (info.photos || s.photos || []).map(p => p.url || p).filter(u => typeof u === 'string'),
      children:          (info.children || []).map(c => ({
        uid:               c.uid || null,
        name:              c.name || null,
        show_name:         c.show_name || null,
        location:          c.location || null,
        std_tag:           c.std_tag || null,
        classified_poi_tag: c.classified_poi_tag || null,
        distance:          c.distance ?? null,
        pv_rate:           c.pv_rate || null,
        content_info:      c.content_info || null,
      })),
    },
  }
}

/**
 * 地点输入提示（v3/suggestion）
 * 返回最多 10 条匹配结果
 */
async function getSuggestion(query, region, opts = {}) {
  const ak = process.env.BAIDU_MAP_AK
  if (!ak) throw new Error('BAIDU_MAP_AK not configured')

  const params = new URLSearchParams({ query, region, output: 'json', ak })
  if (opts.region_limit) params.set('region_limit', 'true')
  if (opts.location)     params.set('location', opts.location)
  if (opts.radius)       params.set('radius', String(opts.radius))
  if (opts.bounds)       params.set('bounds', opts.bounds)

  const data = await httpGet(`https://api.map.baidu.com/place/v3/suggestion?${params}`)
  if (data.status !== 0) throw new Error(`百度API错误: ${data.message || data.status}`)

  return (data.results || []).map(s => ({
    uid:                s.uid || null,
    name:               s.name || null,
    location:           s.location || null,
    province:           s.province || null,
    city:               s.city || null,
    district:           s.district || null,
    address:            s.address || null,
    tag:                s.tag || null,
    classified_poi_tag: s.classified_poi_tag || null,
    children: (s.children || []).map(c => ({
      uid:                c.uid || null,
      show_name:          c.show_name || null,
      std_tag:            c.std_tag || null,
      classified_poi_tag: c.classified_poi_tag || null,
      location:           c.location || null,
      distance:           c.distance ?? null,
      pv_rate:            c.pv_rate || null,
      content_info:       c.content_info || null,
    })),
  }))
}

/**
 * 行政区划区域检索（v3/region）
 * 支持自动翻页（autoPages=true 时最多翻到 total/page_size 页，上限 20 页）
 *
 * opts: { region_limit, type, center, scope, filter, page_size, autoPages, photo_show }
 */
async function searchAdminRegion(query, region, opts = {}) {
  const ak = process.env.BAIDU_MAP_AK
  if (!ak) throw new Error('BAIDU_MAP_AK not configured')

  const pageSize  = Math.min(Number(opts.page_size) || 20, 20)
  const autoPages = opts.autoPages !== false   // 默认自动翻页
  const maxPages  = 20

  const baseParams = {
    query,
    region,
    scope:     String(opts.scope || 2),
    page_size: String(pageSize),
    output:    'json',
    ak,
  }
  if (opts.region_limit) baseParams.region_limit = 'true'
  if (opts.type)         baseParams.type         = opts.type
  if (opts.center)       baseParams.center       = opts.center
  if (opts.filter)       baseParams.filter       = opts.filter
  if (opts.photo_show)   baseParams.photo_show   = 'true'

  const all = []
  let page = 0
  let totalHint = Infinity   // 从第一页 total 字段获取

  while (page < maxPages) {
    const params = new URLSearchParams({ ...baseParams, page_num: String(page) })
    const data = await httpGet(`https://api.map.baidu.com/place/v3/region?${params}`)
    if (data.status !== 0) throw new Error(`百度API错误: ${data.message || data.status}`)

    const results = data.results || []
    if (results.length === 0) break

    // 第一页取 total 上限（API 最多 150）
    if (page === 0 && data.total != null) {
      totalHint = Math.min(Number(data.total), 150)
    }

    all.push(...results.map(s => formatRegionResult(s)))

    if (!autoPages || results.length < pageSize || all.length >= totalHint) break
    page++
    await new Promise(r => setTimeout(r, 300))
  }

  return all
}

function formatRegionResult(s) {
  const info = s.detail_info || {}
  return {
    uid:                s.uid || null,
    name:               s.name || null,
    location:           s.location || null,
    province:           s.province || null,
    city:               s.city || null,
    area:               s.area || null,
    address:            s.address || null,
    status:             s.status || null,
    telephone:          s.telephone || null,
    street_id:          s.street_id || null,
    detail_info: {
      classified_poi_tag: info.classified_poi_tag || null,
      new_alias:          info.new_alias || null,
      type:               info.type || null,
      detail_url:         info.detail_url || null,
      shop_hours:         info.shop_hours || null,
      price:              info.price || null,
      label:              info.label || null,
      overall_rating:     info.overall_rating || null,
      image_num:          info.image_num || null,
      comment_num:        info.comment_num || null,
      brand:              info.brand || null,
      indoor_floor:       info.indoor_floor || null,
      ranking:            info.ranking || null,
      parent_id:          info.parent_id || null,
      best_time:          info.best_time || null,
      sug_time:           info.sug_time || null,
      description:        info.description || null,
      d7_hot_value:       info.d7_hot_value ?? null,
      navi_location:      info.navi_location || null,
      photos:             (info.photos || s.photos || []).map(p => p.url || p).filter(u => typeof u === 'string'),
      children: (info.children || []).map(c => ({
        uid:                c.uid || null,
        name:               c.name || null,
        show_name:          c.show_name || null,
        classified_poi_tag: c.classified_poi_tag || null,
        location:           c.location || null,
        address:            c.address || null,
      })),
    },
  }
}

module.exports = { collectAllSpots, collectSubSpots, formatSpot, collectPolygonSpots, getDetailByUids, getSuggestion, searchAdminRegion }
