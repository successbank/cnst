"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const board_service_1 = require("../services/board.service");
const template_service_1 = require("../services/template.service");
const router = (0, express_1.Router)();
// 견적문의 페이지
router.get('/quote', async (req, res) => {
    try {
        const content = `
        <main class="board-container">
            <div class="container">
                <h1>견적문의</h1>
                <form class="quote-form" method="POST" action="/api/quote">
                    <div class="form-group">
                        <label for="company_name">회사명</label>
                        <input type="text" id="company_name" name="company_name" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_name">담당자명</label>
                        <input type="text" id="contact_name" name="contact_name" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_email">이메일</label>
                        <input type="email" id="contact_email" name="contact_email" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_phone">연락처</label>
                        <input type="tel" id="contact_phone" name="contact_phone" required>
                    </div>
                    <div class="form-group">
                        <label for="product_type">제품종류</label>
                        <select id="product_type" name="product_type" required>
                            <option value="">선택하세요</option>
                            <option value="철근">철근</option>
                            <option value="형강">형강</option>
                            <option value="강판">강판</option>
                            <option value="파이프">파이프</option>
                            <option value="기타">기타</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantity">수량</label>
                        <input type="text" id="quantity" name="quantity" required>
                    </div>
                    <div class="form-group">
                        <label for="content">상세내용</label>
                        <textarea id="content" name="content" rows="5" required></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">견적 요청</button>
                        <button type="reset" class="btn-secondary">초기화</button>
                    </div>
                </form>
            </div>
        </main>
    `;
        const html = await template_service_1.TemplateService.renderTemplate('base', {
            title: '견적문의',
            additionalStyles: '<link rel="stylesheet" href="/css/board.css">',
            content: content,
            additionalScripts: ''
        });
        res.send(html);
    }
    catch (error) {
        console.error('견적문의 페이지 오류:', error);
        res.status(500).send('서버 오류가 발생했습니다.');
    }
});
// 공지사항 목록
router.get('/notice', async (req, res) => {
    try {
        const notices = await board_service_1.BoardService.getBoards('notice', 20);
        const noticeRows = notices.map((notice, index) => {
            const date = new Date(notice.created_at).toLocaleDateString('ko-KR');
            return `
        <tr>
            <td>${notice.is_important ? '<span class="notice-important">공지</span>' : notices.length - index}</td>
            <td class="title"><a href="/notice/${notice.id}">${notice.title}</a></td>
            <td>${notice.author}</td>
            <td>${date}</td>
            <td>${notice.view_count}</td>
        </tr>
      `;
        }).join('');
        const content = `
          <main class="board-container">
              <div class="container">
                  <h1>공지사항</h1>
                  <div class="board-list">
                      <table class="board-table">
                          <thead>
                              <tr>
                                  <th>번호</th>
                                  <th>제목</th>
                                  <th>작성자</th>
                                  <th>작성일</th>
                                  <th>조회수</th>
                              </tr>
                          </thead>
                          <tbody>
                              ${noticeRows}
                          </tbody>
                      </table>
                  </div>
              </div>
          </main>
    `;
        const html = await template_service_1.TemplateService.renderTemplate('base', {
            title: '공지사항',
            additionalStyles: '<link rel="stylesheet" href="/css/board.css">',
            content: content,
            additionalScripts: ''
        });
        res.send(html);
    }
    catch (error) {
        console.error('공지사항 목록 조회 오류:', error);
        res.status(500).send('서버 오류가 발생했습니다.');
    }
});
// 철강뉴스 목록
router.get('/news', async (req, res) => {
    try {
        const newsList = await board_service_1.BoardService.getBoards('news', 12);
        const newsItems = newsList.map((news) => {
            const date = new Date(news.created_at).toLocaleDateString('ko-KR');
            const truncatedContent = news.content.length > 100
                ? news.content.substring(0, 100) + '...'
                : news.content;
            return `
        <article class="news-item">
            <div class="news-thumbnail">
                <img src="${news.thumbnail_url || '/img/news-default.jpg'}" alt="뉴스 이미지">
            </div>
            <div class="news-content">
                <h3><a href="/news/${news.id}">${news.title}</a></h3>
                <p>${truncatedContent}</p>
                <div class="news-meta">
                    <span class="source">${news.source || '충남스틸'}</span>
                    <span class="date">${date}</span>
                </div>
            </div>
        </article>
      `;
        }).join('');
        const content = `
          <main class="board-container">
              <div class="container">
                  <h1>철강뉴스</h1>
                  <div class="news-grid">
                      ${newsItems}
                  </div>
              </div>
          </main>
    `;
        const html = await template_service_1.TemplateService.renderTemplate('base', {
            title: '철강뉴스',
            additionalStyles: '<link rel="stylesheet" href="/css/board.css">',
            content: content,
            additionalScripts: ''
        });
        res.send(html);
    }
    catch (error) {
        console.error('철강뉴스 목록 조회 오류:', error);
        res.status(500).send('서버 오류가 발생했습니다.');
    }
});
exports.default = router;
//# sourceMappingURL=board.routes.js.map