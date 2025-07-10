"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.TemplateService = void 0;
const promises_1 = __importDefault(require("fs/promises"));
const path_1 = __importDefault(require("path"));
class TemplateService {
    /**
     * Include 파일을 읽어오는 메서드
     */
    static async loadInclude(filename) {
        const cacheKey = `include_${filename}`;
        // 캐시 확인
        if (this.templateCache.has(cacheKey)) {
            return this.templateCache.get(cacheKey);
        }
        try {
            const filePath = path_1.default.join(this.includesPath, filename);
            const content = await promises_1.default.readFile(filePath, 'utf-8');
            // 캐시에 저장
            this.templateCache.set(cacheKey, content);
            return content;
        }
        catch (error) {
            console.error(`Include 파일 로드 오류 (${filename}):`, error);
            return '';
        }
    }
    /**
     * HTML 템플릿을 렌더링하는 메서드
     */
    static async renderTemplate(templateName, data = {}) {
        try {
            // header와 footer 로드
            const [header, footer] = await Promise.all([
                this.loadInclude('header.html'),
                this.loadInclude('footer.html')
            ]);
            // 템플릿 파일 로드
            const templatePath = path_1.default.join(__dirname, '../../templates', `${templateName}.html`);
            let template = await promises_1.default.readFile(templatePath, 'utf-8');
            // includes 처리
            template = template.replace('<!-- include:header -->', header);
            template = template.replace('<!-- include:footer -->', footer);
            // 데이터 바인딩 (간단한 템플릿 엔진)
            Object.keys(data).forEach(key => {
                const regex = new RegExp(`{{\\s*${key}\\s*}}`, 'g');
                template = template.replace(regex, data[key]);
            });
            return template;
        }
        catch (error) {
            console.error(`템플릿 렌더링 오류 (${templateName}):`, error);
            throw error;
        }
    }
    /**
     * 캐시 초기화
     */
    static clearCache() {
        this.templateCache.clear();
    }
}
exports.TemplateService = TemplateService;
TemplateService.includesPath = path_1.default.join(__dirname, '../../includes');
// 캐시를 위한 Map
TemplateService.templateCache = new Map();
//# sourceMappingURL=template.service.js.map