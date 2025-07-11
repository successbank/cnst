import openpyxl
import json
import os

def extract_product_data(excel_file, output_json):
    """Excel 파일에서 제품 데이터 추출"""
    try:
        wb = openpyxl.load_workbook(excel_file, data_only=True)
        ws = wb.active
        
        products = []
        
        # 데이터가 시작되는 행 찾기 (보통 2행부터)
        start_row = 2
        
        for row in ws.iter_rows(min_row=start_row, values_only=True):
            if row[0] is None:  # 첫 번째 셀이 비어있으면 종료
                break
                
            # 일반적으로 첫 번째 열이 규격, 두 번째 열이 단중
            specification = str(row[0]).strip() if row[0] else None
            unit_weight = row[1] if row[1] is not None else None
            
            if specification and unit_weight is not None:
                try:
                    # 단중을 float로 변환
                    unit_weight = float(unit_weight)
                    products.append({
                        'specification': specification,
                        'unit_weight': unit_weight
                    })
                except (ValueError, TypeError):
                    print(f"단중 변환 오류: {specification} - {unit_weight}")
        
        # JSON 파일로 저장
        with open(output_json, 'w', encoding='utf-8') as f:
            json.dump(products, f, ensure_ascii=False, indent=2)
        
        print(f"{excel_file} 처리 완료: {len(products)}개 제품")
        return len(products)
        
    except Exception as e:
        print(f"오류 발생 ({excel_file}): {str(e)}")
        return 0

# 파일 목록
files = [
    ('114/8/BS파이프.xlsx', '114/8/bs_pipe_data.json'),
    ('114/8/KS파이프.xlsx', '114/8/ks_pipe_data.json'),
    ('114/8/강관파일.xlsx', '114/8/steel_pipe_pile_data.json'),
    ('114/8/구조관.xlsx', '114/8/structural_pipe_data.json'),
    ('114/8/단관비계.xlsx', '114/8/scaffold_pipe_data.json'),
    ('114/8/복공판.xlsx', '114/8/temporary_deck_data.json'),
    ('114/8/압력배관.xlsx', '114/8/pressure_pipe_data.json'),
    ('114/8/전선관.xlsx', '114/8/conduit_pipe_data.json')
]

total_count = 0
for excel_file, json_file in files:
    count = extract_product_data(excel_file, json_file)
    total_count += count

print(f"\n전체 처리 완료: 총 {total_count}개 제품")