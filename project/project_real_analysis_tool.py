#!/usr/bin/env python3
"""
أداة التحليل الشامل الحقيقي لمشروع AYM ERP
===========================================

هذه الأداة تقوم بمراجعة فعلية للملفات الموجودة في المشروع
وتحديث ملفات التتبع بناءً على الواقع الفعلي للمشروع

المطور: AI Assistant
التاريخ: 2024-12-19
الهدف: تحديث screens_data.json و screen_analysis_report.md بناءً على المراجعة الفعلية
"""

import os
import json
import re
from pathlib import Path
from datetime import datetime

class AYMProjectAnalyzer:
    def __init__(self, project_root="./"):
        self.project_root = Path(project_root)
        self.dashboard_root = self.project_root / "dashboard"
        self.screens_data = []
        self.analysis_results = {
            "total_screens": 0,
            "completed_screens": 0,
            "partial_screens": 0,
            "missing_screens": 0,
            "modules": {},
            "issues": [],
            "recommendations": []
        }

    def extract_routes_from_column_left(self):
        """استخراج جميع المسارات من ملف column_left.php مع التحقق من الأخطاء"""
        column_left_path = self.dashboard_root / "controller/common/column_left.php"
        routes = []
        route_issues = []

        if not column_left_path.exists():
            print(f"❌ ملف column_left.php غير موجود في: {column_left_path}")
            return routes, route_issues

        try:
            with open(column_left_path, 'r', encoding='utf-8') as f:
                content = f.read()

            # البحث عن جميع المسارات في الملف مع أنماط متعددة
            patterns = [
                r"'route'\s*=>\s*'([^']+)'",  # 'route' => 'module/screen'
                r"\$this->url->link\('([^']+)'",  # $this->url->link('module/screen'
                r"'href'\s*=>\s*\$this->url->link\('([^']+)'",  # 'href' => $this->url->link('module/screen'
                r"route=([^&\s'\"]+)",  # route=module/screen
            ]

            for pattern in patterns:
                matches = re.findall(pattern, content)
                routes.extend(matches)

            # إزالة المكررات والتنظيف
            routes = list(set(routes))
            routes = [route for route in routes if '/' in route and not route.startswith('common/')]

            # فحص التضارب في الأسماء (مثل cash)
            module_conflicts = {}
            for route in routes:
                parts = route.split('/')
                if len(parts) >= 2:
                    module = parts[0]
                    screen = parts[1]

                    if screen in module_conflicts and module_conflicts[screen] != module:
                        route_issues.append({
                            'type': 'name_conflict',
                            'screen': screen,
                            'modules': [module_conflicts[screen], module],
                            'routes': [f"{module_conflicts[screen]}/{screen}", route]
                        })
                    else:
                        module_conflicts[screen] = module

            print(f"✅ تم استخراج {len(routes)} مسار من column_left.php")
            if route_issues:
                print(f"⚠️ تم اكتشاف {len(route_issues)} تضارب في الأسماء")

            return sorted(routes), route_issues

        except Exception as e:
            print(f"❌ خطأ في قراءة column_left.php: {e}")
            return routes, route_issues

    def check_file_exists(self, file_path):
        """فحص وجود ملف معين"""
        full_path = self.dashboard_root / file_path
        return full_path.exists()

    def find_similar_files(self, expected_path, search_dir):
        """البحث عن ملفات مشابهة في المجلد"""
        search_path = self.dashboard_root / search_dir
        if not search_path.exists():
            return []

        expected_name = Path(expected_path).stem  # اسم الملف بدون امتداد
        similar_files = []

        try:
            for file_path in search_path.rglob("*.php"):
                if expected_name.lower() in file_path.stem.lower():
                    relative_path = file_path.relative_to(self.dashboard_root)
                    similar_files.append(str(relative_path))

            for file_path in search_path.rglob("*.twig"):
                if expected_name.lower() in file_path.stem.lower():
                    relative_path = file_path.relative_to(self.dashboard_root)
                    similar_files.append(str(relative_path))

        except Exception as e:
            print(f"خطأ في البحث عن ملفات مشابهة: {e}")

        return similar_files

    def check_directory_structure(self, module):
        """فحص هيكل المجلدات لوحدة معينة"""
        structure = {
            'controller': [],
            'model': [],
            'view': [],
            'language': []
        }

        # فحص مجلد controller
        controller_path = self.dashboard_root / f"controller/{module}"
        if controller_path.exists():
            for file_path in controller_path.rglob("*.php"):
                relative_path = file_path.relative_to(self.dashboard_root)
                structure['controller'].append(str(relative_path))

        # فحص مجلد model
        model_path = self.dashboard_root / f"model/{module}"
        if model_path.exists():
            for file_path in model_path.rglob("*.php"):
                relative_path = file_path.relative_to(self.dashboard_root)
                structure['model'].append(str(relative_path))

        # فحص مجلد view
        view_path = self.dashboard_root / f"view/template/{module}"
        if view_path.exists():
            for file_path in view_path.rglob("*.twig"):
                relative_path = file_path.relative_to(self.dashboard_root)
                structure['view'].append(str(relative_path))

        # فحص مجلد language
        language_path = self.dashboard_root / f"language/ar/{module}"
        if language_path.exists():
            for file_path in language_path.rglob("*.php"):
                relative_path = file_path.relative_to(self.dashboard_root)
                structure['language'].append(str(relative_path))

        return structure

    def analyze_screen(self, route):
        """تحليل شاشة واحدة بناءً على المسار مع البحث عن ملفات بأسماء مختلفة"""
        parts = route.split('/')
        if len(parts) < 2:
            return None

        module = parts[0]
        screen = parts[1] if len(parts) == 2 else '_'.join(parts[1:])

        # تحديد مسارات الملفات المتوقعة
        controller_path = f"controller/{module}/{screen}.php"
        model_path = f"model/{module}/{screen}.php"
        view_path = f"view/template/{module}/{screen}.twig"
        language_path = f"language/ar/{module}/{screen}.php"

        # فحص وجود الملفات المتوقعة
        files_status = {
            "controller": "موجود" if self.check_file_exists(controller_path) else "مفقود",
            "model": "موجود" if self.check_file_exists(model_path) else "مفقود",
            "view": "موجود" if self.check_file_exists(view_path) else "مفقود",
            "language": "موجود" if self.check_file_exists(language_path) else "مفقود"
        }

        # البحث عن ملفات بأسماء مختلفة للملفات المفقودة
        actual_files = {
            "controller": controller_path,
            "model": model_path,
            "view": view_path,
            "language": language_path
        }

        alternative_files = {}
        issues = []

        # فحص controller
        if files_status["controller"] == "مفقود":
            similar = self.find_similar_files(controller_path, f"controller/{module}")
            if similar:
                files_status["controller"] = "موجود بأسم مختلف"
                alternative_files["controller"] = similar[0]
                issues.append(f"Controller موجود بأسم مختلف: {similar[0]}")

        # فحص model
        if files_status["model"] == "مفقود":
            similar = self.find_similar_files(model_path, f"model/{module}")
            if similar:
                files_status["model"] = "موجود بأسم مختلف"
                alternative_files["model"] = similar[0]
                issues.append(f"Model موجود بأسم مختلف: {similar[0]}")

        # فحص view
        if files_status["view"] == "مفقود":
            similar = self.find_similar_files(view_path, f"view/template/{module}")
            if similar:
                files_status["view"] = "موجود بأسم مختلف"
                alternative_files["view"] = similar[0]
                issues.append(f"View موجود بأسم مختلف: {similar[0]}")

        # فحص language
        if files_status["language"] == "مفقود":
            similar = self.find_similar_files(language_path, f"language/ar/{module}")
            if similar:
                files_status["language"] = "موجود بأسم مختلف"
                alternative_files["language"] = similar[0]
                issues.append(f"Language موجود بأسم مختلف: {similar[0]}")

        # حساب نسبة الإكمال (الملفات الموجودة بأي اسم تحتسب)
        existing_files = sum(1 for status in files_status.values()
                           if status in ["موجود", "موجود بأسم مختلف"])
        completion_percentage = (existing_files / 4) * 100

        # تحديد الحالة
        if completion_percentage == 100:
            if any("بأسم مختلف" in status for status in files_status.values()):
                status = "مكتمل مع أخطاء أسماء"
            else:
                status = "مكتمل"
        elif completion_percentage == 0:
            status = "مفقود تماماً"
        else:
            status = "جزئي"

        return {
            "route": route,
            "module": module,
            "screen": screen,
            "controller_path": f"dashboard/{controller_path}",
            "model_path": f"dashboard/{model_path}",
            "view_path": f"dashboard/{view_path}",
            "language_path": f"dashboard/{language_path}",
            "files_status": files_status,
            "alternative_files": alternative_files,
            "completion_percentage": completion_percentage,
            "status": status,
            "issues": issues,
            "last_verified": datetime.now().strftime("%Y-%m-%d"),
            "notes": self.generate_notes(files_status, completion_percentage, issues)
        }

    def generate_notes(self, files_status, completion_percentage, issues=None):
        """إنشاء ملاحظات بناءً على حالة الملفات والمشاكل المكتشفة"""
        missing_files = [file_type for file_type, status in files_status.items() if status == "مفقود"]
        wrong_name_files = [file_type for file_type, status in files_status.items() if "بأسم مختلف" in status]

        notes = []

        if completion_percentage == 100:
            if wrong_name_files:
                notes.append(f"مكتمل مع أخطاء أسماء في: {', '.join(wrong_name_files)}")
            else:
                notes.append("مكتمل - جميع المكونات موجودة")
        elif completion_percentage == 0:
            notes.append("مفقود تماماً - يحتاج تطوير كامل")
        else:
            if missing_files:
                notes.append(f"جزئي - مفقود: {', '.join(missing_files)}")
            if wrong_name_files:
                notes.append(f"أخطاء أسماء في: {', '.join(wrong_name_files)}")

        if issues:
            notes.extend(issues)

        return " | ".join(notes) if notes else "بحاجة لمراجعة"

    def analyze_all_screens(self):
        """تحليل جميع الشاشات في المشروع مع اكتشاف المشاكل"""
        print("🔍 بدء التحليل الشامل للمشروع...")

        # استخراج المسارات من column_left.php مع اكتشاف التضارب
        routes, route_issues = self.extract_routes_from_column_left()

        if not routes:
            print("❌ لم يتم العثور على مسارات في column_left.php")
            return

        # حفظ مشاكل المسارات
        self.analysis_results['issues'].extend(route_issues)

        print(f"📊 تحليل {len(routes)} شاشة...")

        screens_with_issues = 0
        screens_with_wrong_names = 0

        for route in routes:
            screen_data = self.analyze_screen(route)
            if screen_data:
                self.screens_data.append(screen_data)

                # إحصاء المشاكل
                if screen_data.get('issues'):
                    screens_with_issues += 1
                if any("بأسم مختلف" in status for status in screen_data['files_status'].values()):
                    screens_with_wrong_names += 1

                # تحديث إحصائيات التحليل
                module = screen_data['module']
                if module not in self.analysis_results['modules']:
                    self.analysis_results['modules'][module] = {
                        'total': 0,
                        'completed': 0,
                        'partial': 0,
                        'missing': 0,
                        'with_issues': 0
                    }

                self.analysis_results['modules'][module]['total'] += 1

                if screen_data.get('issues'):
                    self.analysis_results['modules'][module]['with_issues'] += 1

                if screen_data['status'] in ['مكتمل', 'مكتمل مع أخطاء أسماء']:
                    self.analysis_results['completed_screens'] += 1
                    self.analysis_results['modules'][module]['completed'] += 1
                elif screen_data['status'] == 'جزئي':
                    self.analysis_results['partial_screens'] += 1
                    self.analysis_results['modules'][module]['partial'] += 1
                else:
                    self.analysis_results['missing_screens'] += 1
                    self.analysis_results['modules'][module]['missing'] += 1

        self.analysis_results['total_screens'] = len(self.screens_data)
        self.analysis_results['screens_with_issues'] = screens_with_issues
        self.analysis_results['screens_with_wrong_names'] = screens_with_wrong_names

        print(f"✅ تم تحليل {len(self.screens_data)} شاشة")
        print(f"📈 الإحصائيات:")
        print(f"   - مكتملة: {self.analysis_results['completed_screens']}")
        print(f"   - جزئية: {self.analysis_results['partial_screens']}")
        print(f"   - مفقودة: {self.analysis_results['missing_screens']}")
        print(f"⚠️ المشاكل المكتشفة:")
        print(f"   - شاشات بها مشاكل: {screens_with_issues}")
        print(f"   - شاشات بأسماء خاطئة: {screens_with_wrong_names}")
        print(f"   - تضارب في المسارات: {len(route_issues)}")

    def save_screens_data(self, output_file="screens_data_updated.json"):
        """حفظ بيانات الشاشات المحدثة"""
        try:
            with open(output_file, 'w', encoding='utf-8') as f:
                json.dump(self.screens_data, f, ensure_ascii=False, indent=2)
            print(f"✅ تم حفظ بيانات الشاشات في: {output_file}")
        except Exception as e:
            print(f"❌ خطأ في حفظ بيانات الشاشات: {e}")

    def generate_analysis_report(self, output_file="screen_analysis_report_updated.md"):
        """إنشاء تقرير التحليل المحدث"""
        report_content = f"""# 📋 تقرير التحليل الشامل الحقيقي لمشروع AYM ERP

## 🎯 الهدف الاستراتيجي
تطوير نظام AYM ERP بجودة عالمية تفوق Odoo لتسهيل هجرة العملاء من Odoo إلى AYM ERP

## 📊 الإحصائيات العامة (محدثة - {datetime.now().strftime("%Y-%m-%d")}):
- **إجمالي الشاشات:** {self.analysis_results['total_screens']}
- **الشاشات المكتملة:** {self.analysis_results['completed_screens']} ({self.analysis_results['completed_screens']/self.analysis_results['total_screens']*100:.1f}%)
- **الشاشات المفقودة تماماً:** {self.analysis_results['missing_screens']} ({self.analysis_results['missing_screens']/self.analysis_results['total_screens']*100:.1f}%)
- **الشاشات الجزئية:** {self.analysis_results['partial_screens']} ({self.analysis_results['partial_screens']/self.analysis_results['total_screens']*100:.1f}%)

## 📈 تحليل حسب الوحدات:
"""

        for module, stats in self.analysis_results['modules'].items():
            report_content += f"""
### وحدة {module}:
- **إجمالي:** {stats['total']} شاشة
- **مكتملة:** {stats['completed']} ({stats['completed']/stats['total']*100:.1f}%)
- **جزئية:** {stats['partial']} ({stats['partial']/stats['total']*100:.1f}%)
- **مفقودة:** {stats['missing']} ({stats['missing']/stats['total']*100:.1f}%)
"""

        try:
            with open(output_file, 'w', encoding='utf-8') as f:
                f.write(report_content)
            print(f"✅ تم إنشاء تقرير التحليل في: {output_file}")
        except Exception as e:
            print(f"❌ خطأ في إنشاء تقرير التحليل: {e}")

def main():
    """الدالة الرئيسية"""
    print("🚀 بدء التحليل الشامل الحقيقي لمشروع AYM ERP")
    print("=" * 50)

    analyzer = AYMProjectAnalyzer()
    analyzer.analyze_all_screens()
    analyzer.save_screens_data()
    analyzer.generate_analysis_report()

    print("=" * 50)
    print("✅ تم إكمال التحليل الشامل بنجاح!")

if __name__ == "__main__":
    main()
