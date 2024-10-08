<?php

namespace Modules\Blog\App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Blog\Entities\Blogs;
use Modules\Blog\Entities\CategoryBlog;

class BlogCategoryController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Admin - Danh mục bài viết';
        $blog_category = CategoryBlog::all();
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        if ($startDate && $endDate) {
            $blog_category->whereBetween('created_at', [$startDate, $endDate]);
        }
        $keyword = $request->input('keyword');
        if ($keyword) {
            $blog_category->where('blog_name', 'like', '%' . $keyword . '%');
        }
        return view('admin.blog.Blog_category', compact('blog_category', 'title'));
    }
    public function edit($id)
    {
        $title = 'Admin - Danh mục bài viết - Edit';
        $category_blog = CategoryBlog::all();
        $blog = blogs::with('category_blog')->findOrFail($id);
        return view('admin.blog.edit', compact('blog', 'category_blog', 'title'));
    }
    public function update_blog_category(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'content' => 'required|string',
            'featured' => 'required|integer',
            'blog_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'user_id' => 'required|numeric',
            'category_blog_id' => 'required|numeric',
        ]);
        try {
            $blog = Blogs::findOrFail($id);
            $blog->title = $request->input('title');
            $blog->slug = Str::slug($request->input('title'), '-');
            $blog->content = $request->input('content');
            $blog->featured = $request->input('featured') === 'yes';
            $blog->user_id = $request->input('user_id');
            $blog->category_blog_id = $request->input('category_blog_id');
            if ($request->hasFile('blog_image')) {
                if ($blog->blog_image) {
                    $oldImagePath = public_path($blog->blog_image);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $blog_image = $request->file('blog_image');
                $fileName = time() . '_' . $blog_image->getClientOriginalName();
                $blog_image->move(public_path('blog_image'), $fileName);
                $blog->blog_image = 'image/' . $fileName;
            }
            $blog->save();
            return redirect()->route('blog')->with('success', 'Cập nhật danh mục bài viết thành công!');
        } catch (\Exception $e) {
            dd($e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Cập nhật danh mục bài viết thất bại.');
        }
    }
    public function add()
    {
        $title = 'Admin - Danh mục bài viết - Add';
        return view('admin.blog.add_category', compact('title'));
    }
    public function add_blog_category(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
        ]);
        $blog_category = new CategoryBlog();
        $blog_category->name = $request->input('name');
        $blog_category->save();
        return redirect()->route('blog_category')
            ->with('success', 'Danh mục bài viết đã được tạo thành công.');
    }
    public function destroy($id)
    {
        try {
            $blog = blogs::findOrFail($id);
            $blog->delete();
            return redirect()->route('blog.index')->with('success', 'Danh mục bài viết đã được xóa!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Xóa danh mục bài viết thất bại.');
        }
    }
}
