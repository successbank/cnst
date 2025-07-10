import { pool } from '../config/database';
import { Board, Quote, Notice, News } from '../interfaces/board.interface';
import { ResultSetHeader, RowDataPacket } from 'mysql2';

export class BoardService {
  // 게시글 목록 조회
  static async getBoards(boardType: string, limit: number = 10, offset: number = 0) {
    try {
      const [rows] = await pool.execute<RowDataPacket[]>(
        `SELECT b.*, 
         CASE 
           WHEN b.board_type = 'notice' THEN n.is_important
           ELSE NULL
         END as is_important,
         CASE 
           WHEN b.board_type = 'news' THEN nw.source
           ELSE NULL
         END as source
         FROM boards b
         LEFT JOIN notices n ON b.id = n.board_id AND b.board_type = 'notice'
         LEFT JOIN news nw ON b.id = nw.board_id AND b.board_type = 'news'
         WHERE b.board_type = ?
         ORDER BY b.created_at DESC
         LIMIT ? OFFSET ?`,
        [boardType, limit, offset]
      );
      return rows;
    } catch (error) {
      console.error('게시글 목록 조회 오류:', error);
      throw error;
    }
  }

  // 게시글 상세 조회
  static async getBoardById(id: number) {
    try {
      // 조회수 증가
      await pool.execute(
        'UPDATE boards SET view_count = view_count + 1 WHERE id = ?',
        [id]
      );

      const [rows] = await pool.execute<RowDataPacket[]>(
        `SELECT b.*,
         q.company_name, q.contact_name, q.contact_email, q.contact_phone, 
         q.product_type, q.quantity, q.desired_date, q.status,
         n.is_important, n.attachment_url,
         nw.source, nw.external_url, nw.thumbnail_url
         FROM boards b
         LEFT JOIN quotes q ON b.id = q.board_id AND b.board_type = 'quote'
         LEFT JOIN notices n ON b.id = n.board_id AND b.board_type = 'notice'
         LEFT JOIN news nw ON b.id = nw.board_id AND b.board_type = 'news'
         WHERE b.id = ?`,
        [id]
      );
      
      return rows[0] || null;
    } catch (error) {
      console.error('게시글 상세 조회 오류:', error);
      throw error;
    }
  }

  // 견적문의 생성
  static async createQuote(quoteData: any) {
    const connection = await pool.getConnection();
    
    try {
      await connection.beginTransaction();

      // 게시판 기본 정보 삽입
      const [boardResult] = await connection.execute<ResultSetHeader>(
        'INSERT INTO boards (board_type, title, content, author) VALUES (?, ?, ?, ?)',
        ['quote', quoteData.title || '견적문의', quoteData.content, quoteData.contact_name]
      );

      const boardId = boardResult.insertId;

      // 견적문의 상세 정보 삽입
      await connection.execute(
        `INSERT INTO quotes (board_id, company_name, contact_name, contact_email, 
         contact_phone, product_type, quantity, desired_date) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
        [
          boardId,
          quoteData.company_name,
          quoteData.contact_name,
          quoteData.contact_email,
          quoteData.contact_phone,
          quoteData.product_type,
          quoteData.quantity,
          quoteData.desired_date || null
        ]
      );

      await connection.commit();
      return { success: true, boardId };
    } catch (error) {
      await connection.rollback();
      console.error('견적문의 생성 오류:', error);
      throw error;
    } finally {
      connection.release();
    }
  }

  // 공지사항 생성
  static async createNotice(noticeData: any) {
    const connection = await pool.getConnection();
    
    try {
      await connection.beginTransaction();

      const [boardResult] = await connection.execute<ResultSetHeader>(
        'INSERT INTO boards (board_type, title, content, author) VALUES (?, ?, ?, ?)',
        ['notice', noticeData.title, noticeData.content, noticeData.author || '관리자']
      );

      const boardId = boardResult.insertId;

      await connection.execute(
        'INSERT INTO notices (board_id, is_important, attachment_url) VALUES (?, ?, ?)',
        [boardId, noticeData.is_important || false, noticeData.attachment_url || null]
      );

      await connection.commit();
      return { success: true, boardId };
    } catch (error) {
      await connection.rollback();
      console.error('공지사항 생성 오류:', error);
      throw error;
    } finally {
      connection.release();
    }
  }

  // 철강뉴스 생성
  static async createNews(newsData: any) {
    const connection = await pool.getConnection();
    
    try {
      await connection.beginTransaction();

      const [boardResult] = await connection.execute<ResultSetHeader>(
        'INSERT INTO boards (board_type, title, content, author) VALUES (?, ?, ?, ?)',
        ['news', newsData.title, newsData.content, newsData.author || '편집부']
      );

      const boardId = boardResult.insertId;

      await connection.execute(
        'INSERT INTO news (board_id, source, external_url, thumbnail_url) VALUES (?, ?, ?, ?)',
        [boardId, newsData.source, newsData.external_url || null, newsData.thumbnail_url || null]
      );

      await connection.commit();
      return { success: true, boardId };
    } catch (error) {
      await connection.rollback();
      console.error('철강뉴스 생성 오류:', error);
      throw error;
    } finally {
      connection.release();
    }
  }
}