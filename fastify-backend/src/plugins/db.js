'use strict'

const fp = require('fastify-plugin')
const mysql = require('mysql2/promise')

async function dbPlugin(fastify) {
  const pool = mysql.createPool({
    host:     process.env.DB_HOST || '127.0.0.1',
    port:     parseInt(process.env.DB_PORT || '3306'),
    database: process.env.DB_NAME || 'travel',
    user:     process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    waitForConnections: true,
    connectionLimit: 10,
    charset: 'utf8mb4',
  })

  fastify.decorate('db', pool)

  fastify.addHook('onClose', async () => {
    await pool.end()
  })
}

module.exports = fp(dbPlugin)
