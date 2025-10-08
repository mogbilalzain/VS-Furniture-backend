const fs = require('fs');
const path = require('path');

// Translation dictionary for backend
const translations = {
  // API Response messages
  'تم إنشاء الخاصية بنجاح': 'Property created successfully',
  'تم تحديث الخاصية بنجاح': 'Property updated successfully', 
  'تم حذف الخاصية بنجاح': 'Property deleted successfully',
  'فشل في إنشاء الخاصية': 'Failed to create property',
  'فشل في تحديث الخاصية': 'Failed to update property',
  'فشل في حذف الخاصية': 'Failed to delete property',
  'الخاصية غير موجودة': 'Property not found',
  
  'تم إنشاء القيمة بنجاح': 'Value created successfully',
  'تم تحديث القيمة بنجاح': 'Value updated successfully',
  'تم حذف القيمة بنجاح': 'Value deleted successfully', 
  'فشل في إنشاء القيمة': 'Failed to create value',
  'فشل في تحديث القيمة': 'Failed to update value',
  'فشل في حذف القيمة': 'Failed to delete value',
  'القيمة غير موجودة': 'Value not found',
  
  'تم رفع الملف بنجاح': 'File uploaded successfully',
  'تم تحديث الملف بنجاح': 'File updated successfully',
  'تم حذف الملف بنجاح': 'File deleted successfully',
  'فشل في رفع الملف': 'Failed to upload file',
  'فشل في تحديث الملف': 'Failed to update file', 
  'فشل في حذف الملف': 'Failed to delete file',
  'الملف غير موجود': 'File not found',
  'نوع الملف غير مدعوم': 'File type not supported',
  'حجم الملف كبير جداً': 'File size too large',
  
  'تم إنشاء المنتج بنجاح': 'Product created successfully',
  'تم تحديث المنتج بنجاح': 'Product updated successfully',
  'تم حذف المنتج بنجاح': 'Product deleted successfully',
  'فشل في إنشاء المنتج': 'Failed to create product',
  'فشل في تحديث المنتج': 'Failed to update product',
  'فشل في حذف المنتج': 'Failed to delete product',
  'المنتج غير موجود': 'Product not found',
  
  'تم إنشاء الفئة بنجاح': 'Category created successfully',
  'تم تحديث الفئة بنجاح': 'Category updated successfully',
  'تم حذف الفئة بنجاح': 'Category deleted successfully',
  'فشل في إنشاء الفئة': 'Failed to create category',
  'فشل في تحديث الفئة': 'Failed to update category',
  'فشل في حذف الفئة': 'Failed to delete category',
  'الفئة غير موجودة': 'Category not found',
  
  // Validation messages
  'اسم الخاصية مطلوب': 'Property name is required',
  'اسم القيمة مطلوب': 'Value name is required',
  'اسم المنتج مطلوب': 'Product name is required',
  'اسم الفئة مطلوب': 'Category name is required',
  'الوصف مطلوب': 'Description is required',
  'الصورة مطلوبة': 'Image is required',
  'الملف مطلوب': 'File is required',
  'نوع الإدخال مطلوب': 'Input type is required',
  'الفئة مطلوبة': 'Category is required'
};

function translateFile(filePath) {
  try {
    let content = fs.readFileSync(filePath, 'utf8');
    let hasChanges = false;
    
    // Apply translations
    for (const [arabic, english] of Object.entries(translations)) {
      // Use word boundary regex for more precise matching
      const regex = new RegExp(`'${arabic.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}'`, 'g');
      const doubleQuoteRegex = new RegExp(`"${arabic.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}"`, 'g');
      
      if (content.includes(`'${arabic}'`) || content.includes(`"${arabic}"`)) {
        content = content.replace(regex, `'${english}'`);
        content = content.replace(doubleQuoteRegex, `"${english}"`);
        hasChanges = true;
        console.log(`Translated "${arabic}" to "${english}" in ${filePath}`);
      }
    }
    
    if (hasChanges) {
      fs.writeFileSync(filePath, content, 'utf8');
      console.log(`✅ Updated: ${filePath}`);
    }
    
    return hasChanges;
  } catch (error) {
    console.error(`❌ Error processing ${filePath}:`, error.message);
    return false;
  }
}

function translateDirectory(dirPath, extensions = ['.php']) {
  if (!fs.existsSync(dirPath)) {
    return { total: 0, updated: 0 };
  }
  
  const items = fs.readdirSync(dirPath);
  let totalFiles = 0;
  let updatedFiles = 0;
  
  for (const item of items) {
    const fullPath = path.join(dirPath, item);
    const stat = fs.statSync(fullPath);
    
    if (stat.isDirectory() && !item.startsWith('.') && item !== 'vendor' && item !== 'node_modules') {
      const result = translateDirectory(fullPath, extensions);
      totalFiles += result.total;
      updatedFiles += result.updated;
    } else if (stat.isFile()) {
      const ext = path.extname(item);
      if (extensions.includes(ext)) {
        totalFiles++;
        if (translateFile(fullPath)) {
          updatedFiles++;
        }
      }
    }
  }
  
  return { total: totalFiles, updated: updatedFiles };
}

// Main execution
console.log('🌐 Starting backend translation from Arabic to English...\n');

const directories = [
  './app/Http/Controllers',
  './app/Models',
  './database/seeders'
];

let grandTotal = 0;
let grandUpdated = 0;

for (const dir of directories) {
  if (fs.existsSync(dir)) {
    console.log(`\n📁 Processing directory: ${dir}`);
    const result = translateDirectory(dir);
    console.log(`   Files processed: ${result.total}, Updated: ${result.updated}`);
    grandTotal += result.total;
    grandUpdated += result.updated;
  } else {
    console.log(`⚠️  Directory not found: ${dir}`);
  }
}

console.log(`\n🎉 Backend translation complete!`);
console.log(`📊 Total files processed: ${grandTotal}`);
console.log(`✅ Files updated: ${grandUpdated}`);
console.log(`📈 Success rate: ${grandTotal > 0 ? Math.round((grandUpdated / grandTotal) * 100) : 0}%`);