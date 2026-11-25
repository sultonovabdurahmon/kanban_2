<?php
declare(strict_types=1);

final class UserController extends Controller
{
    /** GET /users */
    public function index(): void
    {
        $this->render('users/index', ['title' => 'Корбарон', 'active' => 'users', 'users' => (new UserRepository())->withStats()]);
    }

    /** POST /users/{id}/delete */
    public function delete(int $id): void
    {
        $users = new UserRepository();
        if ($users->find($id) === null) {
            $this->notFound('Корбар ёфт нашуд.');
        }
        try {
            $users->delete($id);
        } catch (PDOException $e) {
            // 1451 — корбар лоиҳа, вазифа ё шарҳ дорад (ON DELETE RESTRICT): паёми фаҳмо, на саҳифаи 500
            if (($e->errorInfo[1] ?? null) === 1451) {
                $this->redirect('/users?msg=user_has_refs');
            }
            throw $e;
        }
        $this->redirect('/users?msg=user_deleted');
    }
}
