#!/usr/bin/env python3
"""
أداة إنشاء نظرة طائر شاملة لهيكل المشروع الكامل
===============================================

هذه الأداة تقوم بإنشاء tree شامل لجميع ملفات المشروع
في المجلدات الأربعة الأساسية مع تفاصيل كاملة

المطور: AI Assistant
التاريخ: 2024-12-19
الهدف: فهم الهيكل الكامل للمشروع - نظرة طائر حقيقية
"""

import os
from pathlib import Path
from datetime import datetime

class CompleteProjectTreeGenerator:
    def __init__(self, project_root="./"):
        self.project_root = Path(project_root)
        self.dashboard_root = self.project_root / "dashboard"
        
    def generate_tree_for_directory(self, directory, title, file_extensions=None):
        """إنشاء tree لمجلد معين"""
        tree_content = f"\n## 📁 {title}\n"
        tree_content += f"**المسار:** `{directory}`\n\n"
        
        if not directory.exists():
            tree_content += "❌ **المجلد غير موجود**\n"
            return tree_content
        
        # حساب الإحصائيات
        total_files = 0
        modules = {}
        
        try:
            # فحص جميع الملفات
            for item in directory.rglob("*"):
                if item.is_file():
                    # فلترة حسب الامتدادات إذا تم تحديدها
                    if file_extensions and not any(item.name.endswith(ext) for ext in file_extensions):
                        continue
                    
                    total_files += 1
                    
                    # تحديد الوحدة (المجلد الأول)
                    relative_path = item.relative_to(directory)
                    parts = relative_path.parts
                    
                    if len(parts) > 0:
                        module = parts[0]
                        if module not in modules:
                            modules[module] = []
                        modules[module].append(str(relative_path))
            
            # إضافة الإحصائيات
            tree_content += f"**إجمالي الملفات:** {total_files}\n"
            tree_content += f"**عدد الوحدات:** {len(modules)}\n\n"
            
            # إضافة تفاصيل كل وحدة
            for module, files in sorted(modules.items()):
                tree_content += f"### 📂 {module} ({len(files)} ملف)\n"
                tree_content += "```\n"
                for file_path in sorted(files):
                    tree_content += f"{file_path}\n"
                tree_content += "```\n\n"
                
        except Exception as e:
            tree_content += f"❌ **خطأ في قراءة المجلد:** {e}\n"
        
        return tree_content
    
    def generate_complete_tree(self):
        """إنشاء tree شامل لجميع المجلدات"""
        print("🌳 إنشاء نظرة طائر شاملة للمشروع...")
        
        tree_content = f"""# 🌳 نظرة طائر شاملة لهيكل مشروع AYM ERP

## 📅 تاريخ الإنشاء: {datetime.now().strftime("%Y-%m-%d %H:%M:%S")}

## 🎯 الهدف
فهم الهيكل الكامل للمشروع من خلال عرض جميع الملفات في المجلدات الأربعة الأساسية:
- **Controller** - منطق التحكم
- **Model** - منطق البيانات  
- **View** - واجهات المستخدم
- **Language** - ملفات الترجمة

---
"""
        
        # Controller
        controller_path = self.dashboard_root / "controller"
        tree_content += self.generate_tree_for_directory(
            controller_path, 
            "Controller - منطق التحكم", 
            [".php"]
        )
        
        # Model
        model_path = self.dashboard_root / "model"
        tree_content += self.generate_tree_for_directory(
            model_path, 
            "Model - منطق البيانات", 
            [".php"]
        )
        
        # View
        view_path = self.dashboard_root / "view/template"
        tree_content += self.generate_tree_for_directory(
            view_path, 
            "View - واجهات المستخدم", 
            [".twig", ".tpl"]
        )
        
        # Language
        language_path = self.dashboard_root / "language/ar"
        tree_content += self.generate_tree_for_directory(
            language_path, 
            "Language - ملفات الترجمة العربية", 
            [".php"]
        )
        
        # إضافة ملخص شامل
        tree_content += self.generate_summary()
        
        # حفظ الملف
        output_file = "نظرة_طائر_شاملة_للمشروع.md"
        with open(output_file, 'w', encoding='utf-8') as f:
            f.write(tree_content)
        
        print(f"✅ تم إنشاء نظرة طائر شاملة في: {output_file}")
        return tree_content
    
    def generate_summary(self):
        """إنشاء ملخص شامل للمشروع"""
        summary = "\n---\n\n## 📊 الملخص الشامل\n\n"
        
        # حساب الإحصائيات لكل مجلد
        directories = {
            "Controller": self.dashboard_root / "controller",
            "Model": self.dashboard_root / "model", 
            "View": self.dashboard_root / "view/template",
            "Language": self.dashboard_root / "language/ar"
        }
        
        extensions = {
            "Controller": [".php"],
            "Model": [".php"],
            "View": [".twig", ".tpl"], 
            "Language": [".php"]
        }
        
        total_files = 0
        total_modules = set()
        
        for dir_name, dir_path in directories.items():
            if dir_path.exists():
                files_count = 0
                modules = set()
                
                for item in dir_path.rglob("*"):
                    if item.is_file():
                        # فلترة حسب الامتدادات
                        if any(item.name.endswith(ext) for ext in extensions[dir_name]):
                            files_count += 1
                            total_files += 1
                            
                            # تحديد الوحدة
                            relative_path = item.relative_to(dir_path)
                            if len(relative_path.parts) > 0:
                                module = relative_path.parts[0]
                                modules.add(module)
                                total_modules.add(module)
                
                summary += f"- **{dir_name}:** {files_count} ملف في {len(modules)} وحدة\n"
        
        summary += f"\n**الإجمالي:**\n"
        summary += f"- **إجمالي الملفات:** {total_files}\n"
        summary += f"- **إجمالي الوحدات:** {len(total_modules)}\n"
        summary += f"- **الوحدات:** {', '.join(sorted(total_modules))}\n"
        
        return summary

def main():
    """الدالة الرئيسية"""
    print("🚀 بدء إنشاء نظرة طائر شاملة لمشروع AYM ERP")
    print("=" * 60)
    
    generator = CompleteProjectTreeGenerator()
    tree_content = generator.generate_complete_tree()
    
    print("=" * 60)
    print("✅ تم إكمال إنشاء نظرة طائر شاملة بنجاح!")

if __name__ == "__main__":
    main()
