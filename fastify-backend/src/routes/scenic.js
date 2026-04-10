'use strict'

const { collectAllSpots, collectSubSpots, collectPolygonSpots, getDetailByUids, getSuggestion, searchAdminRegion } = require('../scenic/service')

async function scenicRoutes(fastify) {
  /**
   * GET /scenic/spots?region=成都
   * 搜集指定地区所有景区清单
   */
  fastify.get('/spots', {
    schema: {
      querystring: {
        type: 'object',
        required: ['region'],
        properties: {
          region: { type: 'string', minLength: 1, description: '地区名称，如"成都"、"包头市"' },
        },
      },
    },
  }, async (request, reply) => {
    const { region } = request.query

    const spots = await collectAllSpots(region)
    return {
      success: true,
      region,
      total: spots.length,
      data: spots.map(s => {
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
      }),
    }
  })

  /**
   * GET /scenic/sub-spots?name=五当召风景区&region=包头市&radius=5000
   * 搜集某景区周边的子景点
   */
  fastify.get('/sub-spots', {
    schema: {
      querystring: {
        type: 'object',
        required: ['name', 'region'],
        properties: {
          name:     { type: 'string', minLength: 1, description: '主景区名称' },
          region:   { type: 'string', minLength: 1, description: '所在地区' },
          radius:   { type: 'integer', minimum: 100, maximum: 50000, default: 5000, description: '搜索半径（米），默认 5000' },
          keywords: { type: 'string', description: '自定义关键词，逗号分隔，默认：景点,寺庙,佛殿,参观,景区' },
        },
      },
    },
  }, async (request, reply) => {
    const { name, region, radius = 5000, keywords } = request.query

    const keywordList = keywords
      ? keywords.split(',').map(k => k.trim()).filter(Boolean)
      : ['景点', '寺庙', '佛殿', '参观', '景区']

    const result = await collectSubSpots(name, region, radius, keywordList)
    return {
      success: true,
      name,
      region,
      radius,
      main: result.main,
      total: result.subSpots.length,
      subSpots: result.subSpots,
    }
  })
  /**
   * GET /scenic/polygon?bounds=lat1,lng1,lat2,lng2,...&query=景区&tag=旅游景点
   * 多边形区域检索，自动补充地点详情字段
   */
  fastify.get('/polygon', {
    schema: {
      querystring: {
        type: 'object',
        required: ['bounds'],
        properties: {
          bounds: { type: 'string', minLength: 1, description: '多边形顶点坐标，格式：lat1,lng1,lat2,lng2,... 至少3个顶点' },
          query:  { type: 'string', default: '景区', description: '搜索关键词' },
          tag:    { type: 'string', default: '旅游景点', description: 'POI 类型标签' },
        },
      },
    },
  }, async (request, reply) => {
    const { bounds, query = '景区', tag = '旅游景点' } = request.query
    const spots = await collectPolygonSpots(bounds, query, tag)
    return { success: true, total: spots.length, data: spots }
  })

  /**
   * GET /scenic/detail?uid=xxx
   * GET /scenic/detail?uids=uid1,uid2,...
   * 地点详情检索，返回完整 detail_info（含 best_time/sug_time/description/children）
   */
  fastify.get('/detail', {
    schema: {
      querystring: {
        type: 'object',
        properties: {
          uid:  { type: 'string', description: '单个 POI uid' },
          uids: { type: 'string', description: '多个 POI uid，英文逗号分隔，最多 10 个' },
        },
      },
    },
  }, async (request, reply) => {
    const { uid, uids } = request.query
    if (!uid && !uids) {
      return reply.code(400).send({ statusCode: 400, error: 'Bad Request', message: '必须提供 uid 或 uids 参数' })
    }
    const uidList = uids
      ? uids.split(',').map(s => s.trim()).filter(Boolean)
      : [uid]

    const results = await getDetailByUids(uidList)
    return { success: true, total: results.length, data: results }
  })

  /**
   * GET /scenic/suggestion?query=天安门&region=北京&region_limit=true&location=lat,lng&radius=1000&bounds=...
   * 地点输入提示，最多返回 10 条匹配结果
   */
  fastify.get('/suggestion', {
    schema: {
      querystring: {
        type: 'object',
        required: ['query', 'region'],
        properties: {
          query:        { type: 'string', minLength: 1, description: '输入建议关键字' },
          region:       { type: 'string', minLength: 1, description: '城市名或 citycode（仅支持到市级）' },
          region_limit: { type: 'string', description: '是否限制在 region 内，true/false' },
          location:     { type: 'string', description: '中心坐标 lat,lng，用于排序权重' },
          radius:       { type: 'integer', minimum: 1, description: '搜索半径（米），默认 1000' },
          bounds:       { type: 'string', description: '多边形范围，格式同多边形检索' },
        },
      },
    },
  }, async (request, reply) => {
    const { query, region, region_limit, location, radius, bounds } = request.query
    const results = await getSuggestion(query, region, {
      region_limit: region_limit === 'true',
      location,
      radius,
      bounds,
    })
    return { success: true, total: results.length, data: results }
  })

  /**
   * GET /scenic/admin-region
   * 行政区划区域检索（v3/region），支持自动翻页，最多返回 150 条
   */
  fastify.get('/admin-region', {
    schema: {
      querystring: {
        type: 'object',
        required: ['query', 'region'],
        properties: {
          query:        { type: 'string', minLength: 1 },
          region:       { type: 'string', minLength: 1 },
          region_limit: { type: 'string' },
          type:         { type: 'string' },
          center:       { type: 'string', description: 'lat,lng 辅助距离排序' },
          scope:        { type: 'integer', minimum: 1, maximum: 2, default: 2 },
          filter:       { type: 'string', description: 'industry_type:hotel|sort_name:price|sort_rule:0' },
          page_size:    { type: 'integer', minimum: 10, maximum: 20, default: 20 },
          auto_pages:   { type: 'string', description: 'true（默认）自动翻页，false 仅返回第一页' },
          photo_show:   { type: 'string' },
        },
      },
    },
  }, async (request, reply) => {
    const {
      query, region, region_limit, type, center, scope = 2,
      filter, page_size = 20, auto_pages, photo_show,
    } = request.query

    const results = await searchAdminRegion(query, region, {
      region_limit: region_limit === 'true',
      type,
      center,
      scope,
      filter,
      page_size,
      autoPages: auto_pages !== 'false',
      photo_show: photo_show === 'true',
    })
    return { success: true, total: results.length, data: results }
  })
}

module.exports = scenicRoutes
