const fs = require('fs');
const path = require('path');
const mysql = require('mysql2/promise');

const DB_CONFIG = {
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASS || '',
    multipleStatements: true
};

const DB_NAME = 'test_vocacional';
const SQL_FILE_PATH = path.join(__dirname, '..', 'bdd.sql');

async function importDatabase() {
    let connection;

    try {
        console.log('Connecting to MySQL...');
        connection = await mysql.createConnection(DB_CONFIG);

        console.log(`Dropping database "${DB_NAME}" if it exists...`);
        await connection.query(`DROP DATABASE IF EXISTS \`${DB_NAME}\`;`);

        console.log(`Creating database "${DB_NAME}"...`);
        await connection.query(`CREATE DATABASE \`${DB_NAME}\`;`);

        console.log(`Switching to database "${DB_NAME}"...`);
        await connection.changeUser({ database: DB_NAME });

        console.log('Reading SQL file...');
        if (!fs.existsSync(SQL_FILE_PATH)) {
            throw new Error(`SQL file not found at: ${SQL_FILE_PATH}`);
        }

        const sqlContent = fs.readFileSync(SQL_FILE_PATH, 'utf8');

        console.log('Importing database... This may take a moment.');

        await connection.query(sqlContent);

        console.log('Database import completed successfully!');

    } catch (error) {
        console.error('Error importing database:', error);
        process.exit(1);
    } finally {
        if (connection) {
            await connection.end();
        }
    }
}

importDatabase();
