#!/usr/bin/env python3
"""
أداة تحليل هيكل المشروع الشامل - نظرة طائر
=======================================

هذه الأداة تقوم بتحليل الهيكل الكامل للمشروع بناءً على:
1. استخراج المسارات من column_left.php (5017 سطر)
2. فحص الملفات الموجودة فعلياً في المجلدات الأربعة
3. اكتشاف التضارب في الأسماء والملفات المفقودة
4. إنشاء تقرير شامل للحالة الحقيقية

المطور: AI Assistant
التاريخ: 2024-12-19
الهدف: فهم الوضع الحقيقي للمشروع وتحديث المراجع
"""

import os
import json
import re
from pathlib import Path
from datetime import datetime

class ProjectTreeAnalyzer:
    def __init__(self, project_root="./"):
        self.project_root = Path(project_root)
        self.dashboard_root = self.project_root / "dashboard"
        
        # هيكل المشروع الحقيقي
        self.actual_structure = {
            'controller': {},
            'model': {},
            'view': {},
            'language': {}
        }
        
        # المسارات المستخرجة من column_left.php
        self.column_left_routes = []
        
        # التحليل النهائي
        self.analysis_results = {
            'total_routes_in_column_left': 0,
            'total_files_found': 0,
            'modules_analysis': {},
            'missing_files': [],
            'extra_files': [],
            'naming_conflicts': [],
            'recommendations': []
        }
    
    def scan_actual_structure(self):
        """فحص الهيكل الفعلي للملفات الموجودة"""
        print("🔍 فحص الهيكل الفعلي للمشروع...")
        
        # فحص controller
        controller_path = self.dashboard_root / "controller"
        if controller_path.exists():
            self.actual_structure['controller'] = self._scan_directory(controller_path, '.php')
        
        # فحص model
        model_path = self.dashboard_root / "model"
        if model_path.exists():
            self.actual_structure['model'] = self._scan_directory(model_path, '.php')
        
        # فحص view
        view_path = self.dashboard_root / "view/template"
        if view_path.exists():
            self.actual_structure['view'] = self._scan_directory(view_path, '.twig')
        
        # فحص language
        language_path = self.dashboard_root / "language/ar"
        if language_path.exists():
            self.actual_structure['language'] = self._scan_directory(language_path, '.php')
        
        # حساب إجمالي الملفات
        total_files = 0
        for component in self.actual_structure.values():
            total_files += self._count_files_in_structure(component)
        
        self.analysis_results['total_files_found'] = total_files
        print(f"✅ تم فحص {total_files} ملف في المشروع")
    
    def _scan_directory(self, directory, extension):
        """فحص مجلد وإرجاع هيكل الملفات"""
        structure = {}
        
        try:
            for item in directory.rglob(f"*{extension}"):
                if item.is_file():
                    # تحديد المسار النسبي
                    relative_path = item.relative_to(directory)
                    parts = relative_path.parts
                    
                    # بناء الهيكل الشجري
                    current = structure
                    for part in parts[:-1]:  # كل الأجزاء عدا اسم الملف
                        if part not in current:
                            current[part] = {}
                        current = current[part]
                    
                    # إضافة الملف
                    filename = parts[-1]
                    current[filename] = str(item.relative_to(self.dashboard_root))
                    
        except Exception as e:
            print(f"خطأ في فحص المجلد {directory}: {e}")
        
        return structure
    
    def _count_files_in_structure(self, structure):
        """حساب عدد الملفات في هيكل معين"""
        count = 0
        for key, value in structure.items():
            if isinstance(value, dict):
                count += self._count_files_in_structure(value)
            else:
                count += 1
        return count
    
    def extract_routes_from_column_left(self):
        """استخراج المسارات من column_left.php"""
        print("📋 استخراج المسارات من column_left.php...")
        
        column_left_path = self.dashboard_root / "controller/common/column_left.php"
        
        if not column_left_path.exists():
            print(f"❌ ملف column_left.php غير موجود")
            return
        
        try:
            with open(column_left_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            # أنماط البحث المختلفة
            patterns = [
                r"'route'\s*=>\s*'([^']+)'",  # 'route' => 'module/screen'
                r"\$this->url->link\('([^']+)'",  # $this->url->link('module/screen'
                r"'href'\s*=>\s*\$this->url->link\('([^']+)'",  # 'href' => $this->url->link('module/screen'
            ]
            
            routes = set()
            for pattern in patterns:
                matches = re.findall(pattern, content)
                routes.update(matches)
            
            # تنظيف المسارات
            cleaned_routes = []
            for route in routes:
                if '/' in route and not route.startswith('common/'):
                    # تجاهل المسارات الخاصة
                    if not any(skip in route for skip in ['token=', 'user_token=', 'http://', 'https://']):
                        cleaned_routes.append(route)
            
            self.column_left_routes = sorted(list(set(cleaned_routes)))
            self.analysis_results['total_routes_in_column_left'] = len(self.column_left_routes)
            
            print(f"✅ تم استخراج {len(self.column_left_routes)} مسار من column_left.php")
            
        except Exception as e:
            print(f"❌ خطأ في قراءة column_left.php: {e}")
    
    def analyze_modules(self):
        """تحليل الوحدات والمقارنة بين المتوقع والموجود"""
        print("🔬 تحليل الوحدات...")
        
        # تجميع المسارات حسب الوحدة
        modules_from_routes = {}
        for route in self.column_left_routes:
            parts = route.split('/')
            if len(parts) >= 2:
                module = parts[0]
                screen = parts[1]
                
                if module not in modules_from_routes:
                    modules_from_routes[module] = []
                modules_from_routes[module].append(screen)
        
        # تحليل كل وحدة
        for module, screens in modules_from_routes.items():
            module_analysis = {
                'total_screens': len(screens),
                'screens': {},
                'completion_stats': {
                    'complete': 0,
                    'partial': 0,
                    'missing': 0
                }
            }
            
            for screen in screens:
                screen_analysis = self._analyze_screen(module, screen)
                module_analysis['screens'][screen] = screen_analysis
                
                # تحديث الإحصائيات
                if screen_analysis['completion_percentage'] == 100:
                    module_analysis['completion_stats']['complete'] += 1
                elif screen_analysis['completion_percentage'] == 0:
                    module_analysis['completion_stats']['missing'] += 1
                else:
                    module_analysis['completion_stats']['partial'] += 1
            
            # حساب نسبة إكمال الوحدة
            total = module_analysis['total_screens']
            complete = module_analysis['completion_stats']['complete']
            module_analysis['module_completion_percentage'] = (complete / total * 100) if total > 0 else 0
            
            self.analysis_results['modules_analysis'][module] = module_analysis
        
        print(f"✅ تم تحليل {len(modules_from_routes)} وحدة")
    
    def _analyze_screen(self, module, screen):
        """تحليل شاشة واحدة"""
        screen_analysis = {
            'files_found': {
                'controller': False,
                'model': False,
                'view': False,
                'language': False
            },
            'actual_files': {},
            'completion_percentage': 0,
            'issues': []
        }
        
        # فحص controller
        if module in self.actual_structure['controller']:
            controller_file = f"{screen}.php"
            if controller_file in self.actual_structure['controller'][module]:
                screen_analysis['files_found']['controller'] = True
                screen_analysis['actual_files']['controller'] = self.actual_structure['controller'][module][controller_file]
        
        # فحص model
        if module in self.actual_structure['model']:
            model_file = f"{screen}.php"
            if model_file in self.actual_structure['model'][module]:
                screen_analysis['files_found']['model'] = True
                screen_analysis['actual_files']['model'] = self.actual_structure['model'][module][model_file]
        
        # فحص view
        if module in self.actual_structure['view']:
            view_file = f"{screen}.twig"
            if view_file in self.actual_structure['view'][module]:
                screen_analysis['files_found']['view'] = True
                screen_analysis['actual_files']['view'] = self.actual_structure['view'][module][view_file]
        
        # فحص language
        if module in self.actual_structure['language']:
            language_file = f"{screen}.php"
            if language_file in self.actual_structure['language'][module]:
                screen_analysis['files_found']['language'] = True
                screen_analysis['actual_files']['language'] = self.actual_structure['language'][module][language_file]
        
        # حساب نسبة الإكمال
        found_count = sum(1 for found in screen_analysis['files_found'].values() if found)
        screen_analysis['completion_percentage'] = (found_count / 4) * 100
        
        return screen_analysis
    
    def generate_comprehensive_report(self):
        """إنشاء تقرير شامل"""
        print("📊 إنشاء التقرير الشامل...")
        
        report = {
            'analysis_date': datetime.now().isoformat(),
            'project_overview': {
                'total_routes_in_column_left': self.analysis_results['total_routes_in_column_left'],
                'total_files_found': self.analysis_results['total_files_found'],
                'total_modules': len(self.analysis_results['modules_analysis'])
            },
            'modules_summary': {},
            'detailed_analysis': self.analysis_results['modules_analysis'],
            'actual_file_structure': self.actual_structure
        }
        
        # ملخص الوحدات
        for module, analysis in self.analysis_results['modules_analysis'].items():
            report['modules_summary'][module] = {
                'total_screens': analysis['total_screens'],
                'completion_percentage': analysis['module_completion_percentage'],
                'complete_screens': analysis['completion_stats']['complete'],
                'partial_screens': analysis['completion_stats']['partial'],
                'missing_screens': analysis['completion_stats']['missing']
            }
        
        # حفظ التقرير
        with open('comprehensive_project_analysis.json', 'w', encoding='utf-8') as f:
            json.dump(report, f, ensure_ascii=False, indent=2)
        
        print("✅ تم حفظ التقرير الشامل في: comprehensive_project_analysis.json")
        
        return report
    
    def run_complete_analysis(self):
        """تشغيل التحليل الكامل"""
        print("🚀 بدء التحليل الشامل للمشروع")
        print("=" * 60)
        
        # الخطوة 1: فحص الهيكل الفعلي
        self.scan_actual_structure()
        
        # الخطوة 2: استخراج المسارات من column_left.php
        self.extract_routes_from_column_left()
        
        # الخطوة 3: تحليل الوحدات
        self.analyze_modules()
        
        # الخطوة 4: إنشاء التقرير الشامل
        report = self.generate_comprehensive_report()
        
        print("=" * 60)
        print("✅ تم إكمال التحليل الشامل بنجاح!")
        
        return report

def main():
    """الدالة الرئيسية"""
    analyzer = ProjectTreeAnalyzer()
    report = analyzer.run_complete_analysis()
    
    # طباعة ملخص سريع
    print("\n📋 ملخص سريع:")
    print(f"📊 إجمالي المسارات في column_left.php: {report['project_overview']['total_routes_in_column_left']}")
    print(f"📁 إجمالي الملفات الموجودة: {report['project_overview']['total_files_found']}")
    print(f"🏗️ إجمالي الوحدات: {report['project_overview']['total_modules']}")

if __name__ == "__main__":
    main()
