import * as fs from 'fs';
import * as path from 'path';
import mysql from 'mysql2/promise';

// Tiny but valid 1x1 white JPEG (~631 B). Used as placeholder image content
// for fixture sessions. The bytes are real JPEG so the <img> tags in the
// gallery actually load.
const TINY_JPEG_BASE64 =
  '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAUDBAQEAwUEBAQFBQUGBwwIBwcHBw8LCwkMEQ8SEhEPERETFhwXExQaFRERG' +
  'CEYGh0dHx8fExciJCIeJBweHx7/2wBDAQUFBQcGBw4ICA4eFBEUHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eH' +
  'h4eHh4eHh4eHh4eHh4eHh4eHh7/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQ' +
  'oL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoK' +
  'So0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t' +
  '7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcIC' +
  'QoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJ' +
  'ygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0t' +
  'ba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD9/KKKKAP/2Q==';

const TINY_JPEG = Buffer.from(TINY_JPEG_BASE64, 'base64');

const FIXTURE_SESSIONS = [
  '00000000-0000-4000-8000-000000000001',
  '00000000-0000-4000-8000-000000000002',
];

const FIXTURE_FILES = ['photo1.jpg', 'photo2.jpg', 'photo3.jpg', 'photo4.jpg', 'photo5.jpg', 'photo6.jpg'];

async function resetDatabase() {
  const sql = fs.readFileSync(path.join(__dirname, '..', 'db', 'init.sql'), 'utf8');
  const conn = await mysql.createConnection({
    host: '127.0.0.1',
    port: 3307,
    user: 'root',
    password: 'root',
    database: 'sessions',
    multipleStatements: true,
  });
  await conn.query(sql);
  await conn.end();
}

function ensureFixturePhotos() {
  const dataRoot = path.join(__dirname, '..', 'data');
  for (const session of FIXTURE_SESSIONS) {
    const dir = path.join(dataRoot, session);
    fs.mkdirSync(dir, { recursive: true });
    for (const file of FIXTURE_FILES) {
      const p = path.join(dir, file);
      if (!fs.existsSync(p)) {
        fs.writeFileSync(p, TINY_JPEG);
      }
    }
  }

  // Also write a fixture JPEG used by the admin "create session" test.
  const uploadFixture = path.join(__dirname, 'fixtures', 'upload-photo.jpg');
  fs.mkdirSync(path.dirname(uploadFixture), { recursive: true });
  if (!fs.existsSync(uploadFixture)) {
    fs.writeFileSync(uploadFixture, TINY_JPEG);
  }
}

export default async function globalSetup() {
  ensureFixturePhotos();
  await resetDatabase();
}
