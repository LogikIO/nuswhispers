<?php namespace App\Http\Controllers\Admin;

use App\Models\Category as Category;
use App\Models\Confession as Confession;

use Illuminate\Http\Request;

class ConfessionsAdminController extends AdminController {

    public function getIndex($status = 'Pending')
    {
        $query = Confession::orderBy('created_at', 'desc');

        if (\Input::get('category'))
        {
            $query = $query->has('categories', '=', intval(\Input::get('category')));
        }

        if (\Input::get('q'))
        {
            $search = stripslashes(\Input::get('q'));
            $query = $query->where('content', 'LIKE', "%$search%");
        }

        if ($status != 'all')
        {
            $query = $query->where('status', '=', ucfirst($status));
        }

        $confessions = $query->paginate(10);

        return view('admin.confessions.index', array(
            'confessions' => $confessions,
            'categoryOptions' => array_merge(array('All Categories' => 0), Category::orderBy('confession_category', 'asc')->lists('confession_category_id', 'confession_category'))
        ));
    }

    public function getEdit($id)
    {

    }

    public function postEdit()
    {

    }

    public function getApprove($id)
    {
        // @TODO: Move this to a repository
        $confession = Confession::findOrFail($id);

        try {
            // Post confession to Facebook
            if ($confession->images) {
                $response = \Facebook::post('/' . env('FACEBOOK_PAGE_ID', '') . '/photos', [
                    'message' => $confession->content,
                    'url'  => $confession->images,
                ], $this->getPageToken())->getGraphObject();

                $confession->fb_post_id = explode('_', $response['post_id'])[1];
            } else {
                $response = \Facebook::post('/' . env('FACEBOOK_PAGE_ID', '') . '/feed', [
                    'message' => $confession->content,
                ], $this->getPageToken())->getGraphObject();

                $confession->fb_post_id = explode('_', $response['id'])[1];
            }

            // @TODO: Log the approval

            $confession->status = 'Approved';
            $confession->save();

            return \Redirect::back()->withMessage('Confession successfully approved and posted.')->with('alert-class', 'alert-success');
        } catch (\Exception $e) {
            return \Redirect::back()->withMessage('Error approving confession: ' . $e->getMessage())->with('alert-class', 'alert-danger');
        }
    }

    public function getReject($id)
    {
        // @TODO: Move this to a repository
        $confession = Confession::findOrFail($id);

        try {
            if ($confession->fb_post_id) {
                \Facebook::delete('/' . env('FACEBOOK_PAGE_ID', '') . '_' . $confession->fb_post_id, [], $this->getPageToken());
            }

            // @TODO: Log the approval

            $confession->fb_post_id = '';
            $confession->status = 'Rejected';
            $confession->save();

            return \Redirect::back()->withMessage('Confession successfully rejected.')->with('alert-class', 'alert-success');
        } catch (\Exception $e) {
            return \Redirect::back()->withMessage('Error rejecting confession: ' . $e->getMessage())->with('alert-class', 'alert-danger');
        }
    }

    protected function getPageToken()
    {
        $profile = \Auth::user()->profiles()->where('provider_name', '=', 'facebook')->get();
        if (count($profile) !== 1) {
            return false;
        }
        return $profile[0]->page_token;
    }

    public function getDelete($id)
    {
        $confession = Confession::findOrFail($id);

        try {
            if ($confession->fb_post_id) {
                \Facebook::delete('/' . env('FACEBOOK_PAGE_ID', '') . '_' . $confession->fb_post_id, [], $this->getPageToken());
            }

            // @TODO: Log the approval

            $confession->delete();

            return \Redirect::back()->withMessage('Confession successfully deleted.')->with('alert-class', 'alert-success');
        } catch (\Exception $e) {
            return \Redirect::back()->withMessage('Error deleting confession: ' . $e->getMessage())->with('alert-class', 'alert-danger');
        }
    }

}
