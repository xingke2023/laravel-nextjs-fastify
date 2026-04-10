'use strict'

/**
 * 讲解点 API
 *
 * GET /commentary/spot/:spotId          — 景区所有讲解点（APP 初始化下载）
 * GET /commentary/node/:nodeId          — 某景点的讲解点列表
 * GET /commentary/nearby                — GPS 附近触发（核心接口）
 */
async function commentaryRoutes(fastify) {

  /**
   * 获取景区全部讲解点（APP 进景区时一次性下载）
   * GET /commentary/spot/:spotId
   */
  fastify.get('/spot/:spotId', {
    schema: {
      params: {
        type: 'object',
        required: ['spotId'],
        properties: { spotId: { type: 'integer' } },
      },
    },
  }, async (request) => {
    const { spotId } = request.params

    const [rows] = await fastify.db.execute(
      `SELECT cp.id, cp.scenic_node_id, cp.title, cp.content,
              cp.audio_url, cp.image_url,
              cp.lat, cp.lng, cp.trigger_radius, cp.duration, cp.sort_order,
              sn.name AS node_name
       FROM commentary_points cp
       LEFT JOIN scenic_nodes sn ON sn.id = cp.scenic_node_id
       WHERE cp.scenic_spot_id = ? AND cp.is_published = 1
       ORDER BY cp.sort_order ASC`,
      [spotId]
    )

    return { success: true, spotId, total: rows.length, data: rows }
  })

  /**
   * 获取某景点的讲解点
   * GET /commentary/node/:nodeId
   */
  fastify.get('/node/:nodeId', {
    schema: {
      params: {
        type: 'object',
        required: ['nodeId'],
        properties: { nodeId: { type: 'integer' } },
      },
    },
  }, async (request) => {
    const { nodeId } = request.params

    const [rows] = await fastify.db.execute(
      `SELECT id, title, content, audio_url, image_url,
              lat, lng, trigger_radius, duration, sort_order
       FROM commentary_points
       WHERE scenic_node_id = ? AND is_published = 1
       ORDER BY sort_order ASC`,
      [nodeId]
    )

    return { success: true, nodeId, total: rows.length, data: rows }
  })

  /**
   * GPS 附近讲解点触发接口（移动端实时调用）
   * GET /commentary/nearby?lat=&lng=&spotId=&radius=
   *
   * 返回在用户坐标 radius 米范围内的讲解点，
   * 按距离升序，客户端拿到后判断是否自动播报
   */
  fastify.get('/nearby', {
    schema: {
      querystring: {
        type: 'object',
        required: ['lat', 'lng', 'spotId'],
        properties: {
          lat:    { type: 'number' },
          lng:    { type: 'number' },
          spotId: { type: 'integer' },
          radius: { type: 'integer', default: 50, minimum: 10, maximum: 500,
                    description: '搜索半径（米），默认 50' },
        },
      },
    },
  }, async (request) => {
    const { lat, lng, spotId, radius = 50 } = request.query

    // 使用 Haversine 公式在 MySQL 内计算球面距离，只返回触发范围内的点
    // trigger_radius 是各讲解点自身设定的触发半径，取两者中较大值作为判断依据
    const [rows] = await fastify.db.execute(
      `SELECT id, scenic_node_id, title, content, audio_url, image_url,
              lat, lng, trigger_radius, duration, sort_order,
              ROUND(
                6371000 * 2 * ASIN(SQRT(
                  POW(SIN((RADIANS(lat) - RADIANS(?)) / 2), 2) +
                  COS(RADIANS(?)) * COS(RADIANS(lat)) *
                  POW(SIN((RADIANS(lng) - RADIANS(?)) / 2), 2)
                ))
              ) AS distance_m
       FROM commentary_points
       WHERE scenic_spot_id = ?
         AND is_published = 1
         AND lat IS NOT NULL AND lng IS NOT NULL
       HAVING distance_m <= GREATEST(trigger_radius, ?)
       ORDER BY distance_m ASC`,
      [lat, lat, lng, spotId, radius]
    )

    return {
      success:  true,
      userLat:  lat,
      userLng:  lng,
      spotId,
      radius,
      total:    rows.length,
      data:     rows,
    }
  })
}

module.exports = commentaryRoutes
