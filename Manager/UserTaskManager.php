<?php

namespace Smirik\CourseBundle\Manager;

use Smirik\CourseBundle\Model\TaskQuery;
use Smirik\CourseBundle\Model\UserTaskQuery;
use Smirik\CourseBundle\Model\UserTask;

class UserTaskManager
{

    /**
     * Creates user tasks for given lesson
     * @param  integer $user_id
     * @param  integer $lesson_id
     * @return void
     */
    public function generate($user, $lesson)
    {
        $tasks_ids = TaskQuery::create()
            ->select('Id')
            ->filterByLessonId($lesson->getId())
            ->find()
            ->toArray();

        $users_tasks_ids = UserTaskQuery::create()
            ->select('TaskId')
            ->filterByLessonId($lesson->getId())
            ->filterByUserId($user->getId())
            ->find()
            ->toArray();

        $diff = array_diff($tasks_ids, $users_tasks_ids);
        foreach ($diff as $id) {
            $user_task = new UserTask();
            $user_task->setLessonId($lesson->getId());
            $user_task->setTaskId($id);
            $user_task->setUserId($user->getId());
            $user_task->setStatus(0);
            $user_task->save();
            unset($user_task);
        }
    }

    /**
     * @param $lessons_ids
     * @param $user
     * @return array
     */
    public function getByLessons($lessons_ids, $user)
    {
        $user_tasks = UserTaskQuery::create()
            ->filterByLessonId($lessons_ids)
            ->filterByUserId($user->getId())
            ->find();

        $tasks = array();
        $marks = array();
        $count = array();
        foreach ($lessons_ids as $id) {
            $tasks[$id] = array('accepted' => 0, 'in_progress' => 0, 'draft' => 0);
            $marks[$id] = 0;
            $count[$id] = 0;
        }

        foreach ($user_tasks as $user_task) {
            switch ($user_task->getStatus()) {
                case 3:
                    $tasks[$user_task->getLessonId()]['accepted']++;
                    break;

                case 2:
                case 1:
                    $tasks[$user_task->getLessonId()]['in_progress']++;

                case 0:
                default:
                    $tasks[$user_task->getLessonId()]['draft']++;
                    break;
            }
            if ($user_task->getMark() > 0) {
                $marks[$user_task->getLessonId()] = $marks[$user_task->getLessonId()] + $user_task->getMark();
                $count[$user_task->getLessonId()]++;
            }
        }

        return array(
            'tasks' => $tasks,
            'marks' => $marks,
            'count' => $count,
        );
    }

}